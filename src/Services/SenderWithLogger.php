<?php

namespace Romandots\Smser\Services;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\SenderInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InsufficientBalance;

class SenderWithLogger extends SenderServiceDecorator
{
    public function __construct(
        SenderInterface $sender,
        protected ?LoggerInterface $logger,
    ) {
        parent::__construct($sender);
    }

    public function send(string $phone, string $message): MessageCost
    {

        try {
            $messageCost = $this->sender->send($phone, $message);

            $this->logger?->info(
                "SMS sent successfully",
                [
                    'phone' => $phone,
                    'message' => $message,
                    'message_length' => mb_strlen($message),
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
                    'message_length' => mb_strlen($message),
                    'message_cost' => $throwable instanceof InsufficientBalance ? $throwable->cost : null,
                    'remaining_balance' => $throwable instanceof InsufficientBalance ? $throwable->balance : null
                ]
            );

            throw $throwable;
        }
    }
}