<?php

namespace Romandots\Smser\Contracts;

interface ProviderFactoryInterface
{
    public function sender(): SmsSenderInterface;

    public function balanceChecker(): BalanceCheckerInterface;

    public function costCalculator(): CostCalculatorInterface;
}