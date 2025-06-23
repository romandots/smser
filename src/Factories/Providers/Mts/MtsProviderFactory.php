<?php

namespace Romandots\Smser\Factories\Providers\Mts;

use Romandots\Smser\Contracts\BalanceCheckerInterface;
use Romandots\Smser\Contracts\CostCalculatorInterface;
use Romandots\Smser\Contracts\ProviderFactoryInterface;
use Romandots\Smser\Contracts\SmsSenderInterface;

class MtsProviderFactory implements ProviderFactoryInterface
{
    private MtsProviderService $service;

    public function __construct(array $config)
    {
        $this->service = new MtsProviderService($config);
    }

    public function sender(): SmsSenderInterface
    {
        return $this->service;
    }

    public function balanceChecker(): BalanceCheckerInterface
    {
        return $this->service;
    }

    public function costCalculator(): CostCalculatorInterface
    {
        return $this->service;
    }
}