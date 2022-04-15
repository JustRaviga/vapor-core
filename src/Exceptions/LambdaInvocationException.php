<?php

namespace Laravel\Vapor\Exceptions;

class LambdaInvocationException extends \Exception
{
    /**
     * LambdaInvocationException constructor.
     * @param $message
     */
    public function __construct(string $invocationId)
    {
        parent::__construct("Invocation {$invocationId} failed.");
    }
}
