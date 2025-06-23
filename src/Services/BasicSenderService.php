<?php

namespace Romandots\Smser\Services;

use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\ProviderFactoryResolverInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\DTO\SMS;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\ServiceUnavailable;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Factories\SmsProviderFactory;
use Romandots\Smser\Value\Message;
use Romandots\Smser\Value\PhoneNumber;

readonly class BasicSenderService implements SenderServiceInterface
{
    public function __construct(
        protected ProviderDeterminationInterface $providerDeterminationService,
        protected ProviderFactoryResolverInterface $providerFactoryResolver,
    ) {
    }

    /**
     * @param string $phone
     * @param string $message
     * @return MessageCost
     * @throws InvalidArgument
     * @throws UnknownProvider
     * @throws InsufficientBalance
     * @throws ServiceUnavailable
     * @throws \Throwable
     */
    public function send(string $phone, string $message): MessageCost
    {
        $this->checkBalance($phone, $message);

        $sms = $this->buildSms($phone, $message);
        $provider = $this->providerFactoryResolver->getProviderFactory($sms->provider);
        $cost = $provider->sender()->send($sms);
        $balance = $provider->balanceChecker()->checkBalance();

        return new MessageCost($cost, $balance);
    }

    /**
     * @param string $phone
     * @param string $message
     * @return bool
     * @throws InvalidArgument
     * @throws UnknownProvider
     * @throws ServiceUnavailable
     */
    public function canSend(string $phone, string $message): bool
    {
        try {
            $this->checkBalance($phone, $message);
            return true;
        } catch (InsufficientBalance) {
            return false;
        }
    }

    /**
     * @param string $phone
     * @param string $message
     * @return void
     * @throws InsufficientBalance
     */
    protected function checkBalance(string $phone, string $message): void
    {
        $sms = $this->buildSms($phone, $message);
        $provider = $this->providerFactoryResolver->getProviderFactory($sms->provider);

        $balance = $provider->balanceChecker()->checkBalance();
        $cost = $provider->costCalculator()->calculateMessageCost($sms->message->value);
        if ($balance < $cost) {
            throw new InsufficientBalance($balance, $cost);
        }
    }

    protected function buildSms(string $phone, string $message): SMS
    {
        $phoneNumber = new PhoneNumber($phone);
        $provider = $this->providerDeterminationService->determineProvider($phoneNumber);
        $msg = new Message($message);

        return new SMS($phoneNumber, $msg, $provider);
    }
}