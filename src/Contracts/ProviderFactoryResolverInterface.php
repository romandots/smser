<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\Value\Provider;

interface ProviderFactoryResolverInterface
{
    public function getProviderFactory(Provider $provider): ProviderFactoryInterface;

    public function hasFactory(Provider $provider): bool;
}