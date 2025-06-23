<?php

namespace Romandots\Smser\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\TestDox;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Services\ProviderDeterminationService;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\PhoneNumber;

class ProviderDeterminationServiceTest extends TestCase
{
    #[TestDox('Should throw UnknownProvider for any phone number')]
    public function test_throws_unknown_provider(): void
    {
        $service = new ProviderDeterminationService();
        $phoneNumber = new PhoneNumber('79251234567');

        $this->expectException(UnknownProvider::class);
        $this->expectExceptionMessage('Unknown provider for phone number 79251234567');

        $service->determineProvider($phoneNumber);
    }
}
