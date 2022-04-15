<?php

namespace Laravel\Vapor\Exceptions\Sentry;

use Sentry\State\Scope;
use function Sentry\captureException;
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
        if (config('vapor.sentry_dsn')) {
            init(['dsn' => config('vapor.sentry_dsn')]);
            self::$initialized = true;
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
