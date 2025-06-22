<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\Exceptions\InsufficientBalance;

interface BalanceCheckerInterface
{
    public function checkBalance(): float;
}