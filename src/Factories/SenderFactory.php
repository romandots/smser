<?php

namespace Romandots\Smser\Factories;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\SenderInterface;
use Romandots\Smser\Services\SenderService;
use Romandots\Smser\Services\SenderWithLogger;
use Romandots\Smser\Services\SenderWithRetriesService;

class SenderFactory
{
    public static function create(
        ?ProviderDeterminationInterface $providerDetermination = null,
        ?LoggerInterface $logger = null,
        array $options = [],
    ): SenderInterface {
        $sender = new SenderService($providerDetermination);

        if ($options['withRetries'] ?? false) {
            $sender = new SenderWithRetriesService(
                $sender,
                $logger,
                $options['maxAttempts'] ?? null,
                $options['retryDelayMs'] ?? null,
            );
        }

        if (($options['withExtraLogging'] ?? false) && $logger) {
            $sender = new SenderWithLogger($sender, $logger);
        }

        return $sender;
    }

}