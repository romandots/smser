<?php

namespace Romandots\Smser\Factories;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\Services\BasicSenderService;
use Romandots\Smser\Services\SenderWithLoggerService;
use Romandots\Smser\Services\SenderWithRetriesService;

class SenderFactory
{
    public static function create(
        ?ProviderDeterminationInterface $providerDetermination = null,
        ?LoggerInterface $logger = null,
        array $options = [],
    ): SenderServiceInterface {
        $sender = new BasicSenderService($providerDetermination);

        if ($options['withRetries'] ?? false) {
            $sender = new SenderWithRetriesService(
                $sender,
                $logger,
                $options['maxAttempts'] ?? null,
                $options['retryDelayMs'] ?? null,
            );
        }

        if (($options['withExtraLogging'] ?? false) && $logger) {
            $sender = new SenderWithLoggerService($sender, $logger);
        }

        return $sender;
    }

}