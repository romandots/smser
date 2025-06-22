<?php

namespace Romandots\Smser\Services;

use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Value\Provider;
use Romandots\Smser\Value\PhoneNumber;

class ProviderDeterminationService
{
    public static function determineProvider(PhoneNumber $phoneNumber): Provider
    {
        throw new UnknownProvider("Unknown provider for phone number {$phoneNumber}");
    }
}