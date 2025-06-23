<?php

namespace Romandots\Smser\Tests\Unit\Factories;

use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\ProviderFactoryResolverInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Factories\Providers\Test\TestProviderFactory;
use Romandots\Smser\Factories\SenderFactory;
use Romandots\Smser\Services\ProviderFactoryResolver;
use Romandots\Smser\Services\SenderWithLoggerService;
use Romandots\Smser\Services\SenderWithRetriesService;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\Provider;

class SenderFactoryTest extends TestCase
{
    #[TestDox('Should create sender with custom configuration')]
    public function test_create_with_custom_configuration(): void
    {
        $determination = $this->createMock(ProviderDeterminationInterface::class);
        $determination->method('determineProvider')->willReturn(Provider::MTS);

        $resolver = new ProviderFactoryResolver();
        $resolver->registerFactory(Provider::MTS, new TestProviderFactory());

        $service = SenderFactory::create($determination, $resolver);

        $this->assertInstanceOf(SenderServiceInterface::class, $service);

        $result = $service->send('79251234567', 'hi');

        $this->assertInstanceOf(MessageCost::class, $result);
    }

    #[TestDox('Should wrap sender with retries and logging when options enabled')]
    public function test_create_with_decorators(): void
    {
        $determination = $this->createMock(ProviderDeterminationInterface::class);
        $determination->method('determineProvider')->willReturn(Provider::MTS);

        $resolver = new ProviderFactoryResolver();
        $resolver->registerFactory(Provider::MTS, new TestProviderFactory());

        $logger = $this->createMock(LoggerInterface::class);

        $service = SenderFactory::create(
            $determination,
            $resolver,
            $logger,
            ['withRetries' => true, 'withExtraLogging' => true, 'maxAttempts' => 1, 'retryDelayMs' => 0]
        );

        $this->assertInstanceOf(SenderWithLoggerService::class, $service);
        $innerService = (function () { return $this->sender; })->call($service);
        $this->assertInstanceOf(SenderWithRetriesService::class, $innerService);

        $result = $service->send('79251234567', 'hi');
        $this->assertInstanceOf(MessageCost::class, $result);
    }

    #[TestDox('Should create sender using custom providers callable')]
    public function test_create_with_custom_providers_method(): void
    {
        $determination = $this->createMock(ProviderDeterminationInterface::class);
        $determination->method('determineProvider')->willReturn(Provider::MTS);

        $service = SenderFactory::createWithCustomProviders(
            function (ProviderFactoryResolverInterface $registry) {
                $registry->registerFactory(Provider::MTS, new TestProviderFactory());
            },
            $determination
        );

        $this->assertInstanceOf(SenderServiceInterface::class, $service);

        $result = $service->send('79251234567', 'hello');
        $this->assertInstanceOf(MessageCost::class, $result);
    }

    #[TestDox('Should return supported providers list')]
    public function test_get_supported_providers(): void
    {
        $providers = SenderFactory::getSupportedProviders();

        $this->assertContains(Provider::MTS, $providers);
        $this->assertContains(Provider::BEELINE, $providers);
        $this->assertContains(Provider::MEGAFON, $providers);
        $this->assertContains(Provider::TELE2, $providers);
    }
}
