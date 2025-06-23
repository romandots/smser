<?php

namespace Romandots\Smser\Factories\Providers\Test;

use Romandots\Smser\Contracts\BalanceCheckerInterface;
use Romandots\Smser\Contracts\CostCalculatorInterface;
use Romandots\Smser\Contracts\ProviderFactoryInterface;
use Romandots\Smser\Contracts\SmsSenderInterface;

class TestProviderFactory implements ProviderFactoryInterface
{

    public function sender(): SmsSenderInterface
    {
        return new TestProviderClient();
    }

    public function balanceChecker(): BalanceCheckerInterface
    {
        return new TestProviderClient();
    }

    public function costCalculator(): CostCalculatorInterface
    {
        return new TestProviderClient();
    }
}