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
        return $this->client->getBalance();
    }

    public function calculateMessageCost(string $message): float
    {
        $length = mb_strlen($message);
        $segmentSize = preg_match('/[^\x00-\x7F]/u', $message) ? 70 : 160;
        $segments = (int) ceil($length / $segmentSize);

        return $segments * 2.0; // simple fixed rate per segment
    }

    public function send(SMS $sms): float
    {
        $this->client->sendSms($sms->phoneNumber->value, $sms->message->value);

        return $this->calculateMessageCost($sms->message->value);
    }
}