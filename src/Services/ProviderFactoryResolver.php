<?php

namespace Romandots\Smser\Services;

use Romandots\Smser\Contracts\ProviderFactoryInterface;
use Romandots\Smser\Contracts\ProviderFactoryRegistryInterface;
use Romandots\Smser\Contracts\ProviderFactoryResolverInterface;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Value\Provider;

class ProviderFactoryResolver implements ProviderFactoryResolverInterface, ProviderFactoryRegistryInterface
{
    private array $registry = [];

    public function getProviderFactory(Provider $provider): ProviderFactoryInterface
    {
        return $this->registry[$provider->value]
            ?? throw new UnknownProvider("Provider {$provider->value} has no factory associated with it.");
    }

    public function registerFactory(Provider $provider, ProviderFactoryInterface $factory): void
    {
        $this->registry[$provider->value] = $factory;
    }

    public function hasFactory(Provider $provider): bool
    {
        return isset($this->registry[$provider->value]);
    }

    /**
     * @return array<Provider>
     */
    public function getRegisteredProviders(): array
    {
        $providers = [];
        foreach (array_keys($this->registry) as $providerName) {
            foreach (Provider::cases() as $provider) {
                if ($provider->value === $providerName) {
                    $providers[] = $provider;
                    break;
                }
            }
        }

        return $providers;
    }

    public function reset(): void
    {
        $this->registry = [];
    }
}