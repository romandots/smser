<?php

namespace Romandots\Smser\Services;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\ServiceUnavailable;

class SenderWithRetriesService extends SenderServiceDecorator
{
    public function __construct(
        SenderServiceInterface $senderService,
        protected ?LoggerInterface $logger = null,
        protected int $maxAttempts = 3,
        protected int $retryDelayMs = 1000
    ) {
        parent::__construct($senderService);
    }

    public function send(string $phone, string $message): MessageCost
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                return $this->sender->send($phone, $message);
            } catch (ServiceUnavailable $exception) {
                $lastException = $exception;

                $this->logger?->warning(
                    "SMS send attempt {$attempt}/{$this->maxAttempts} failed",
                    [
                        'phone' => $phone,
                        'message' => $message,
                        'error' => $exception->getMessage(),
                        'attempt' => $attempt,
                        'max_attempts' => $this->maxAttempts
                    ]
                );

                // Don't sleep after the last attempt
                if ($attempt < $this->maxAttempts) {
                    $delay = $this->calculateDelay($attempt);
                    $this->logger?->debug("Waiting {$delay}ms before retry");
                    usleep($delay * 1000);
                }
            } catch (\Throwable $exception) {
                // Don't retry on other exceptions
                throw $exception;
            }
        }

        throw $lastException ?? new ServiceUnavailable('All retry attempts failed');
    }

    /**
     * Calculate delay with exponential backoff: 1s, 2s, 4s, 8s...
     */
    protected function calculateDelay(int $attempt): int
    {
        return $this->retryDelayMs * (2 ** ($attempt - 1));
    }
}