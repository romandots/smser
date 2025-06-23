<?php

namespace Romandots\Smser\Factories\Providers\Test;

use Romandots\Smser\Contracts\BalanceCheckerInterface;
use Romandots\Smser\Contracts\CostCalculatorInterface as CostCalculatorInterfaceAlias;
use Romandots\Smser\Contracts\SmsSenderInterface;
use Romandots\Smser\DTO\SMS;

class TestProviderClient implements SmsSenderInterface, BalanceCheckerInterface, CostCalculatorInterfaceAlias
{

    public function send(SMS $sms): float
    {
        return $sms->message->length() * 2.0;
    }

    public function checkBalance(): float
    {
        return 100.0;
    }

    public function calculateMessageCost(string $message): float
    {
        return strlen($message) * 2.0;
    }
}