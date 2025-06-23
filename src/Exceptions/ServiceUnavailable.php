<?php

namespace Romandots\Smser\Exceptions;

class ServiceUnavailable extends \RuntimeException
{

    public function __construct(
        string $message,
        public readonly ?string $serviceName = null,
        public readonly ?int $httpCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}