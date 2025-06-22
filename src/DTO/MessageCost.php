<?php

namespace Romandots\Smser\DTO;

readonly class MessageCost
{
    public function __construct(public float $messageCost, public float $remainingBalance)
    {
    }
}