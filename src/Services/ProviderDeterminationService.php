<?php

namespace Romandots\Smser\Services;

use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Value\Provider;
use Romandots\Smser\Value\PhoneNumber;

class ProviderDeterminationService implements ProviderDeterminationInterface
{
    public function determineProvider(PhoneNumber $phoneNumber): Provider
    {
        throw new UnknownProvider("Unknown provider for phone number {$phoneNumber}");
    }
}