<?php

namespace Romandots\Smser\Factories;

use Romandots\Smser\Contracts\SmsProviderFactoryInterface;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Value\Provider;

class SmsProviderFactory
{
    /** @var array<Provider, SmsProviderFactoryInterface>  */
    protected static array $instances = [];

    public static function getInstance(Provider $provider): SmsProviderFactoryInterface
    {
        if (!isset(self::$instances[$provider->value])) {
            self::$instances[$provider->value] = match($provider) {
                // Provider::MTS => new MtsProviderFactory(),
                // Provider::MEGAFON => new MegafonProviderFactory(),
                // Provider::BEELINE => new BeelineProviderFactory(),
                // Provider::TELE2 => new Tele2ProviderFactory(),
                default => throw new  UnknownProvider("Provider {$provider->value} not implemented"),
            };
        }

        return self::$instances[$provider->value];
    }
}