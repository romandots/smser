<?php

namespace Romandots\Smser\Exceptions;

class InsufficientBalance extends \LogicException
{
    public function __construct(readonly public float $balance, readonly public float $cost)
    {
        parent::__construct("Insufficient balance: {$balance}. Message cost: {$cost}");
    }
}