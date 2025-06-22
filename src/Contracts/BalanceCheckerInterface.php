<?php

namespace Romandots\Smser\Contracts;

interface BalanceCheckerInterface
{
    public function checkBalance(): float;
}