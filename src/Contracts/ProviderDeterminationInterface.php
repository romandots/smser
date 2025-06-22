<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Value\Provider;

interface ProviderDeterminationInterface
{

    public function determineProvider(PhoneNumber $phoneNumber): Provider;
}