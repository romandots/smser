<?php

namespace Romandots\Smser\Contracts;

interface SmsProviderFactoryInterface
{
    public function sender(): SmsSenderInterface;

    public function balanceChecker(): BalanceCheckerInterface;

    public function costCalculator(): CostCalculatorInterface;
}