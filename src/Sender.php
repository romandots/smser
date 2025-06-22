<?php

namespace Romandots\Smser;

use Psr\Log\LoggerInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\DTO\SMS;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\ServiceUnavailable;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Factories\SmsProviderFactory;
use Romandots\Smser\Services\ProviderDeterminationService;
use Romandots\Smser\Value\Message;
use Romandots\Smser\Value\PhoneNumber;

readonly class Sender
{
    public function __construct(
        protected ProviderDeterminationService $providerDeterminationService,
        protected ?LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $phone
     * @param string $message
     * @return MessageCost Remaining balance
     * @throws InvalidArgument
     * @throws UnknownProvider
     * @throws InsufficientBalance
     * @throws ServiceUnavailable
     * @throws \Throwable
     */
    public function send(string $phone, string $message): MessageCost
    {
        try {
            $phoneNumber = new PhoneNumber($phone);
            $provider = $this->providerDeterminationService->determineProvider($phoneNumber);
            $msg = new Message($message);
            $sms = new SMS($phoneNumber, $msg, $provider);

            $smsProviderService = SmsProviderFactory::getInstance($sms->provider);
            $balance = $smsProviderService->balanceChecker()->checkBalance();

            if ($balance <= 0) {
                throw new InsufficientBalance;
            }

            $smsProviderService->sender()->send($sms);
            $remainingBalance = $smsProviderService->balanceChecker()->checkBalance();

            $messageCost = new MessageCost($balance - $remainingBalance, $remainingBalance);

            $this->logger?->info(
                "SMS sent successfully",
                [
                    'phone' => $phoneNumber->value,
                    'message' => $msg->value,
                    'message_length' => $msg->length(),
                    'message_cost' => $messageCost->messageCost,
                    'remaining_balance' => $messageCost->remainingBalance,
                ]
            );

            return $messageCost;
        } catch (\Throwable $throwable) {
            $errorMessage = $throwable->getMessage();
            $this->logger?->error(
                "SMS sending failed" . ($errorMessage ? ': ' . $errorMessage : ''),
                [
                    'exception' => get_class($throwable),
                    'phone' => $phone,
                    'message' => $message,
                    'message_length' => strlen($message),
                    'message_cost' => $messageCost?->messageCost,
                    'remaining_balance' => $messageCost?->remainingBalance ?? $balance ?? null,
                ]
            );

            throw $throwable;
        }
    }
}