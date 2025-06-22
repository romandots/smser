<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\Exceptions\InsufficientBalance;

/**
 * @throws InsufficientBalance
 */
interface BalanceCheckerInterface
{
    public function checkBalance(): void;
}