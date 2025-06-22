<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\DTO\MessageCost;

interface SenderInterface
{
    public function send(string $phone, string $message): MessageCost;
    public function canSend(string $phone, string $message): bool;
}