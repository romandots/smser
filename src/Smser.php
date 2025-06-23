<?php

namespace Romandots\Smser;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\Contracts\SmserInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Factories\SenderFactory;

/**
 * Main facade
 */
class Smser implements SmserInterface
{
    protected ?SenderServiceInterface $sender = null;
    protected mixed $customProvidersCallable = null;

    /**
     * @param ProviderDeterminationInterface|null $providerDetermination Сервис определения провайдера
     * @param callable|null $customProvidersCallable Функция настройки кастомных провайдеров
     * @param LoggerInterface|null $logger Логгер
     * @param array $options Опции для декораторов
     */
    public function __construct(
        protected ?ProviderDeterminationInterface $providerDetermination = null,
        ?callable $customProvidersCallable = null,
        protected ?LoggerInterface $logger = null,
        protected array $options = [],
    ) {
        $this->customProvidersCallable = $customProvidersCallable;
    }

    /**
     * Send SMS
     */
    public function send(string $phone, string $message): MessageCost
    {
        return $this->getSender()->send($phone, $message);
    }

    /**
     * Can the SMS be sent?
     */
    public function canSend(string $phone, string $message): bool
    {
        return $this->getSender()->canSend($phone, $message);
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * With extra logging
     */
    public function withLogging(LoggerInterface $logger): self
    {
        $this->options['withExtraLogging'] = true;
        $this->logger = $logger;
        $this->invalidateSender();

        return $this;
    }

    /**
     * With retries on failures
     */
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
        $this->invalidateSender();

        return $this;
    }

    /**
     * With custom providers
     *
     * @param callable $configurator function(ProviderFactoryRegistryInterface $registry): void
     */
    public function withCustomProviders(callable $configurator): self
    {
        $this->customProvidersCallable = $configurator;
        $this->invalidateSender();

        return $this;
    }

    protected function getSender(): SenderServiceInterface
    {
        if ($this->sender === null) {
            $this->sender = $this->createSender();
        }

        return $this->sender;
    }

    protected function createSender(): SenderServiceInterface
    {
        if ($this->customProvidersCallable !== null) {
            return SenderFactory::createWithCustomProviders(
                $this->customProvidersCallable,
                $this->providerDetermination,
                $this->logger,
                $this->options,
            );
        }

        return SenderFactory::create(
            $this->providerDetermination,
            null,
            $this->logger,
            $this->options,
        );
    }

    protected function invalidateSender(): void
    {
        $this->sender = null;
    }
}