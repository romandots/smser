<?php

namespace Romandots\Smser\Contracts;

use Psr\Log\LoggerInterface;
use Romandots\Smser\DTO\MessageCost;

interface SmserInterface
{

    public function send(string $phone, string $message): MessageCost;

    public function canSend(string $phone, string $message): bool;

    public function withLogging(LoggerInterface $logger): self;

    public function withRetries(int $maxAttempts = 3, int $retryDelayMs = 1000): self;
}