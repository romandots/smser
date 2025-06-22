<?php

namespace Romandots\Smser\Contracts;

interface CostCalculatorInterface
{
    public function calculateMessageCost(string $message): float;
}