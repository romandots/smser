<?php

namespace Romandots\Smser\Factories;

use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\ProviderFactoryRegistryInterface;
use Romandots\Smser\Contracts\ProviderFactoryResolverInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\Factories\Providers\Mts\MtsProviderFactory;
use Romandots\Smser\Factories\Providers\Test\TestProviderFactory;
use Romandots\Smser\Services\BasicSenderService;
use Romandots\Smser\Services\ProviderDeterminationService;
use Romandots\Smser\Services\ProviderFactoryResolver;
use Romandots\Smser\Services\SenderWithLoggerService;
use Romandots\Smser\Services\SenderWithRetriesService;
use Romandots\Smser\Value\Provider;

class SenderFactory
{
    public static function create(
        ?ProviderDeterminationInterface $providerDetermination = null,
        ?ProviderFactoryResolverInterface $providerFactoryResolver = null,
        ?LoggerInterface $logger = null,
        array $options = [],
        array $config = [],
    ): SenderServiceInterface {
        $providerDetermination ??= new ProviderDeterminationService();
        $providerFactoryResolver ??= self::createConfiguredFactoryResolver($config);
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

    public static function createConfiguredFactoryResolver(array $config): ProviderFactoryRegistryInterface
    {
        $factoryResolver = new ProviderFactoryResolver();

        // @todo Implement real clients
        $factoryResolver->registerFactory(Provider::MTS, new MtsProviderFactory($config['mts'] ?? []));
        $factoryResolver->registerFactory(Provider::BEELINE, new TestProviderFactory());
        $factoryResolver->registerFactory(Provider::MEGAFON, new TestProviderFactory());
        $factoryResolver->registerFactory(Provider::TELE2, new TestProviderFactory());

        return $factoryResolver;
    }

    public static function createTestSendService(
        ProviderDeterminationInterface $providerDeterminationService,
        ProviderFactoryResolverInterface $providerFactoryResolver,
    ): SenderServiceInterface {
        return new BasicSenderService($providerDeterminationService, $providerFactoryResolver);
    }

    /**
     * @param callable $configurator function(ProviderFactoryRegistryInterface $registry, array $config): void
     * @param ProviderDeterminationInterface|null $providerDetermination
     * @param LoggerInterface|null $logger
     * @param array $options
     * @param array $config
     * @return SenderServiceInterface
     */
    public static function createWithCustomProviders(
        callable $configurator,
        ?ProviderDeterminationInterface $providerDetermination = null,
        ?LoggerInterface $logger = null,
        array $options = [],
        array $config = [],
    ): SenderServiceInterface {
        $resolver = new ProviderFactoryResolver();

        $configurator($resolver, $config);

        return self::create($providerDetermination, $resolver, $logger, $options);
    }

    public static function getSupportedProviders(): array
    {
        return self::createConfiguredFactoryResolver()->getRegisteredProviders() ?? [];
    }
}