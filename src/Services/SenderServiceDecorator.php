<?php

namespace Romandots\Smser\Services;

use Romandots\Smser\Contracts\SenderInterface;
use Romandots\Smser\DTO\MessageCost;

class SenderServiceDecorator implements SenderInterface
{

    public function __construct(protected SenderInterface $sender)
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