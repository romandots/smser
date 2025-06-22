<?php

namespace Romandots\Smser\Services;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\DTO\SMS;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\ServiceUnavailable;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Factories\SmsProviderFactory;
use Romandots\Smser\Value\Message;
use Romandots\Smser\Value\PhoneNumber;

readonly class SenderService
{
    public function __construct(
        protected ProviderDeterminationInterface $providerDeterminationService,
        protected ?LoggerInterface $logger,
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
        try {
            if (!$this->canSend($phone, $message)) {
                throw new InsufficientBalance;
            }

            $sms = $this->buildSms($phone, $message);
            $provider = SmsProviderFactory::getInstance($sms->provider);
            $cost = $provider->sender()->send($sms);
            $balance = $provider->balanceChecker()->checkBalance();

            $this->logger?->info(
                "SMS sent successfully",
                [
                    'phone' => $sms->phoneNumber->value,
                    'message' => $sms->message->value,
                    'message_length' => $sms->message->length(),
                    'message_cost' => $cost,
                    'remaining_balance' => $balance,
                ]
            );

            return new MessageCost($cost, $balance);
        } catch (\Throwable $throwable) {
            $errorMessage = $throwable->getMessage();
            $this->logger?->error(
                "SMS sending failed" . ($errorMessage ? ': ' . $errorMessage : ''),
                [
                    'exception' => get_class($throwable),
                    'phone' => $phone,
                    'message' => $message,
                    'message_length' => strlen($message),
                    'message_cost' => $cost ?? null,
                    'remaining_balance' => $balance ?? null,
                ]
            );

            throw $throwable;
        }
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
        $sms = $this->buildSms($phone, $message);
        $provider = SmsProviderFactory::getInstance($sms->provider);

        $balance = $provider->balanceChecker()->checkBalance();
        $cost = $provider->costCalculator()->calculateMessageCost($sms->message);

        return $balance >= $cost;
    }

    protected function buildSms(string $phone, string $message): SMS
    {
        $phoneNumber = new PhoneNumber($phone);
        $provider = $this->providerDeterminationService->determineProvider($phoneNumber);
        $msg = new Message($message);

        return new SMS($phoneNumber, $msg, $provider);
    }

    public static function create(?LoggerInterface $logger = null): self
    {
        return new self(
            new ProviderDeterminationService(),
            $logger,
        );
    }
}