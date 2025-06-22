<?php

namespace Romandots\Smser;

use Romandots\Smser\DTO\SMS;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Factories\SmsProviderFactory;

class Sender
{
    /**
     * @param string $phone
     * @param string $message
     * @return void
     * @throws InvalidArgument
     * @throws UnknownProvider
     * @throws InsufficientBalance
     */
    public function send(string $phone, string $message): void
    {
        $sms = SMS::make($phone, $message);
        $provider = SmsProviderFactory::getInstance($sms->provider);
        $provider->balanceChecker()->checkBalance();
        $provider->sender()->send($sms->phoneNumber, $sms->message);
    }
}