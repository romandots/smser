<?php

namespace Romandots\Smser;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\SenderInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Factories\SenderFactory;

class Smser
{
    protected ?SenderInterface $sender;
    protected ?LoggerInterface $logger;
    protected array $options;

    public function __construct(protected ?ProviderDeterminationInterface $providerDetermination = null)
    {

    }

    public function send(string $phone, $message): MessageCost
    {
        if (is_null($this->sender)) {
            $this->sender = SenderFactory::create($this->providerDetermination, $this->logger, $this->options);
        }

        return $this->sender->send($phone, $message);
    }

    public function withLogging(LoggerInterface $logger): self
    {
        $this->options['withExtraLogging'] = true;
        $this->logger = $logger;

        return $this;
    }

    public function withRetries(int $maxAttempts = 3, int $retryDelayMs = 1000): self
    {
        $this->options['withRetries'] = true;
        $this->options['maxAttempts'] = $maxAttempts;
        $this->options['retryDelayMs'] = $retryDelayMs;

        return $this;
    }
}