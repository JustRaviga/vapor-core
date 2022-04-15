<?php

namespace Laravel\Vapor\Runtime;

use Exception;
use Laravel\Vapor\Exceptions\LambdaInvocationException;
use Laravel\Vapor\Exceptions\SentryHandler;
use Throwable;

class LambdaRuntime
{
    use NotifiesLambda;

    /**
     * The Lambda API URL.
     * @var string
     */
    protected $apiUrl;

    /**
     * Create a new Lambda runtime.
     * @param string $apiUrl
     * @return void
     */
    public function __construct($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * Create new Lambda runtime from the API environment variable.
     * @return static
     */
    public static function fromEnvironmentVariable()
    {
        return new static(getenv('AWS_LAMBDA_RUNTIME_API'));
    }

    /**
     * Handle the next Lambda invocation.
     * @param callable $callback
     * @return void
     */
    public function nextInvocation(callable $callback)
    {
        [$invocationId, $event] = LambdaInvocation::next($this->apiUrl);

        $_ENV['AWS_REQUEST_ID'] = $invocationId;

        try {
            $this->notifyLambdaOfResponse($invocationId, $callback($invocationId, $event));
        } catch (Throwable $error) {
            $this->handleException($invocationId, $error);

            throw new LambdaInvocationException($invocationId);
        }
    }

    /**
     * Inform Lambda of an invocation failure.
     * @param string $invocationId
     * @param \Throwable $error
     * @return void
     */
    public function handleException(string $invocationId, Throwable $error)
    {
        $errorMessage = $error instanceof Exception
            ? 'Uncaught '.get_class($error).': '.$error->getMessage()
            : $error->getMessage();

        fwrite(STDERR, sprintf(
            "Fatal error: %s in %s:%d\nStack trace:\n%s".PHP_EOL,
            $errorMessage,
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        ));

        $this->notifyLambdaOfError($invocationId, [
            'errorMessage' => $error->getMessage(),
            'errorType' => get_class($error),
            'stackTrace' => explode(PHP_EOL, $error->getTraceAsString()),
        ]);

        SentryHandler::reportException($error, [
            'invocationId' => $invocationId,
            'errorType' => get_class($error),
            'stackTrace' => explode(PHP_EOL, $error->getTraceAsString()),
            'Memory now:' => round(memory_get_usage() / 1024 / 1024, 2).' MB',
            'Memory peak:' => round(memory_get_peak_usage() / 1024 / 1024, 2).' MB',
            'CPU Avg' => round(sys_getloadavg()[0], 2).'%',
        ]);
    }
}
