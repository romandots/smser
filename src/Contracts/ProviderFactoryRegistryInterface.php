<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\Value\Provider;

interface ProviderFactoryRegistryInterface
{
    public function registerFactory(Provider $provider, ProviderFactoryInterface $factory): void;

    public function getRegisteredProviders(): array;

    public function reset(): void;
}