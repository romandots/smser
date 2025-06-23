<?php

namespace Romandots\Smser;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\Contracts\SmserInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Factories\SenderFactory;

class Smser implements SmserInterface
{
    protected ?SenderServiceInterface $sender = null;
    protected ?LoggerInterface $logger = null;
    protected array $options = [];

    public function __construct(protected ?ProviderDeterminationInterface $providerDetermination = null)
    {
    }

    protected function getSender(): SenderServiceInterface
    {
        if (is_null($this->sender)) {
            $this->sender = SenderFactory::create($this->providerDetermination, $this->logger, $this->options);
        }

        return $this->sender;
    }

    public function send(string $phone, $message): MessageCost
    {
        return $this->getSender()->send($phone, $message);
    }

    public function canSend(string $phone, $message): bool
    {
        return $this->getSender()->canSend($phone, $message);
    }

    public static function create(): self
    {
        return new self();
    }

    public function withLogging(LoggerInterface $logger): self
    {
        $this->options['withExtraLogging'] = true;
        $this->logger = $logger;
        $this->sender = null;

        return $this;
    }

    public function withRetries(int $maxAttempts = 3, int $retryDelayMs = 1000): self
    {
        if ($maxAttempts < 1) {
            throw new InvalidArgument('Max attempts must be at least 1');
        }
        if ($retryDelayMs < 0) {
            throw new InvalidArgument('Retry delay cannot be negative');
        }

        $this->options['withRetries'] = true;
        $this->options['maxAttempts'] = $maxAttempts;
        $this->options['retryDelayMs'] = $retryDelayMs;
        $this->sender = null;

        return $this;
    }
}