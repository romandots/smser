<?php

namespace Romandots\Smser\Factories;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\ProviderFactoryRegistryInterface;
use Romandots\Smser\Contracts\ProviderFactoryResolverInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\Services\BasicSenderService;
use Romandots\Smser\Services\ProviderDeterminationService;
use Romandots\Smser\Services\ProviderFactoryResolver;
use Romandots\Smser\Services\SenderWithLoggerService;
use Romandots\Smser\Services\SenderWithRetriesService;

class SenderFactory
{
    public static function create(
        ?ProviderDeterminationInterface $providerDetermination = null,
        ?ProviderFactoryResolverInterface $providerFactoryResolver = null,
        ?LoggerInterface $logger = null,
        array $options = [],
    ): SenderServiceInterface {
        $providerDetermination ??= new ProviderDeterminationService();
        $providerFactoryResolver ??= self::createConfiguredFactoryResolver();
        $sender = new BasicSenderService($providerDetermination, $providerFactoryResolver);

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

    public static function createConfiguredFactoryResolver(): ProviderFactoryRegistryInterface
    {
        $factoryResolver = new ProviderFactoryResolver();
        // $resolver->registerFactory(Provider::MTS, new MtsProviderFactory());

        return $factoryResolver;
    }

    public static function createTestSendService(
        ProviderDeterminationInterface $providerDeterminationService,
        ProviderFactoryResolverInterface $providerFactoryResolver,
    ): SenderServiceInterface {
        return new BasicSenderService($providerDeterminationService, $providerFactoryResolver);
    }

    /**
     * @param callable $configurator function(ProviderFactoryRegistryInterface $registry): void
     * @param ProviderDeterminationInterface|null $providerDetermination
     * @param LoggerInterface|null $logger
     * @param array $options
     * @return SenderServiceInterface
     */
    public static function createWithCustomProviders(
        callable $configurator,
        ?ProviderDeterminationInterface $providerDetermination = null,
        ?LoggerInterface $logger = null,
        array $options = []
    ): SenderServiceInterface {
        $resolver = new ProviderFactoryResolver();

        $configurator($resolver);

        return self::create($providerDetermination, $resolver, $logger, $options);
    }

    public static function getSupportedProviders(): array
    {
        return self::createConfiguredFactoryResolver()->getRegisteredProviders() ?? [];
    }
}