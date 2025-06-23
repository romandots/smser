<?php

namespace Romandots\Smser\Clients\Mts;

use Romandots\Smser\Exceptions\InvalidConfiguration;

class Client
{
    public function __construct(array $config)
    {
        $this->validateConfig($config);
    }

    /**
     * @param array $config
     * @return void
     * @throws InvalidConfiguration
     */
    private function validateConfig(array $config): void
    {
        // @todo implement this
    }
}