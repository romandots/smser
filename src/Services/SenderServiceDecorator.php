<?php

namespace Romandots\Smser\Services;

use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;

abstract class SenderServiceDecorator implements SenderServiceInterface
{

    public function __construct(protected SenderServiceInterface $sender)
    {
    }

    public function send(string $phone, string $message): MessageCost
    {
        return $this->sender->send($phone, $message);
    }

    public function canSend(string $phone, string $message): bool
    {
        return $this->sender->canSend($phone, $message);
    }
}