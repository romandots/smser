<?php

namespace Romandots\Smser\Services;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\ServiceUnavailable;
use Romandots\Smser\Exceptions\UnknownProvider;

readonly class SenderWithRetriesService extends SenderService
{
    /** @var array<string, int> */
    protected array $retries;

    public function __construct(
        protected ProviderDeterminationInterface $providerDeterminationService,
        protected ?LoggerInterface $logger,
        protected int $maxTries = 3,
    ) {
        parent::__construct($providerDeterminationService, $logger);
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
    public function sendWithRetries(string $phone, string $message): MessageCost
    {
        $key = md5("{$phone}:{$message}");
        if (!isset($this->retries[$key])) {
            $this->retries[$key] = 0;
        }
        $lastException = null;

        for ($i = $this->retries[$key]; $i < $this->maxTries; $i++) {
            try {
                return $this->send($phone, $message);
            } catch (ServiceUnavailable $exception) {
                $lastException = $exception;
                $this->logger?->warning("SMS send attempt {$i} failed, retrying...");
                usleep(1000 * $i);
            } finally {
                unset($this->retries[$key]);
            }
        }

        throw $lastException;
    }

    public static function create(?LoggerInterface $logger = null, int $maxTries = 3): self
    {
        return new self(
            new ProviderDeterminationService(),
            $logger,
            $maxTries,
        );
    }
}