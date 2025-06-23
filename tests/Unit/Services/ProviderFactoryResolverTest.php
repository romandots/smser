<?php

namespace Romandots\Smser\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\TestDox;
use Romandots\Smser\Contracts\ProviderFactoryInterface;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Services\ProviderFactoryResolver;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\Provider;

class ProviderFactoryResolverTest extends TestCase
{
    #[TestDox('Should register and resolve provider factories')]
    public function test_register_and_resolve_factories(): void
    {
        $resolver = new ProviderFactoryResolver();
        $factory = $this->createMock(ProviderFactoryInterface::class);

        $resolver->registerFactory(Provider::MTS, $factory);

        $this->assertTrue($resolver->hasFactory(Provider::MTS));
        $this->assertSame($factory, $resolver->getProviderFactory(Provider::MTS));
    }

    #[TestDox('Should return registered providers as Provider enums')]
    public function test_get_registered_providers(): void
    {
        $resolver = new ProviderFactoryResolver();
        $factory = $this->createMock(ProviderFactoryInterface::class);
        $resolver->registerFactory(Provider::MTS, $factory);
        $resolver->registerFactory(Provider::BEELINE, $factory);

        $providers = $resolver->getRegisteredProviders();

        $this->assertContains(Provider::MTS, $providers);
        $this->assertContains(Provider::BEELINE, $providers);
        $this->assertCount(2, $providers);
    }

    #[TestDox('Should throw UnknownProvider for unregistered providers')]
    public function test_unknown_provider_exception(): void
    {
        $resolver = new ProviderFactoryResolver();

        $this->expectException(UnknownProvider::class);

        $resolver->getProviderFactory(Provider::TELE2);
    }

    #[TestDox('Should reset registry state')]
    public function test_reset_registry(): void
    {
        $resolver = new ProviderFactoryResolver();
        $factory = $this->createMock(ProviderFactoryInterface::class);
        $resolver->registerFactory(Provider::MTS, $factory);
        $resolver->reset();

        $this->assertFalse($resolver->hasFactory(Provider::MTS));
        $this->expectException(UnknownProvider::class);
        $resolver->getProviderFactory(Provider::MTS);
    }
}
