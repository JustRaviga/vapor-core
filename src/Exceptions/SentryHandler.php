<?php

namespace Laravel\Vapor\Exceptions;

use Sentry\State\Scope;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\configureScope;
use function Sentry\init;

class SentryHandler
{
    /**
     * Defines whether Stripe was initialized
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Init Sentry
     * @return void
     */
    public static function init()
    {
        fwrite(STDERR, 'Sentry DSN: '.config('vapor.sentry_dsn').' for env - '.$_ENV['APP_ENV'].PHP_EOL);
        if (config('vapor.sentry_dsn')) {
            init(['dsn' => config('vapor.sentry_dsn'), 'environment' => $_ENV['APP_ENV']]);
            self::$initialized = true;
            fwrite(STDERR, 'Sentry initialized.'.PHP_EOL);
        }
    }

    /**
     * Report about exception to Sentry
     * @param \Throwable $exception
     * @param array $extra
     * @return void
     */
    public static function reportException(\Throwable $exception, array $extra = [])
    {
        if (!self::$initialized) {
            return;
        }

        configureScope(fn(Scope $scope) => $scope->setExtras($extra));
        captureException($exception);
    }
}
