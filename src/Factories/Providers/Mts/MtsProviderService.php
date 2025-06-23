<?php

namespace Romandots\Smser\Factories\Providers\Mts;

use Romandots\Smser\Clients\Mts\Client;
use Romandots\Smser\Contracts\BalanceCheckerInterface;
use Romandots\Smser\Contracts\CostCalculatorInterface;
use Romandots\Smser\Contracts\SmsSenderInterface;
use Romandots\Smser\DTO\SMS;

class MtsProviderService implements SmsSenderInterface, BalanceCheckerInterface, CostCalculatorInterface
{
    private Client $client;

    public function __construct(array $config)
    {
        $this->client = new Client($config);
    }

    public function checkBalance(): float
    {
        // TODO: Implement checkBalance() method.
    }

    public function calculateMessageCost(string $message): float
    {
        // TODO: Implement calculateMessageCost() method.
    }

    public function send(SMS $sms): float
    {
        // TODO: Implement send() method.
    }
}