<?php

namespace Romandots\Smser\Tests\Unit;

use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\ProviderFactoryRegistryInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Factories\Providers\Test\TestProviderFactory;
use Romandots\Smser\Services\BasicSenderService;
use Romandots\Smser\Services\SenderWithLoggerService;
use Romandots\Smser\Services\SenderWithRetriesService;
use Romandots\Smser\Smser;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Value\Provider;

class SmserTest extends TestCase
{
    /**
     * Create a configured Smser instance for tests
     */
    private function createSmser(): Smser
    {
        $determination = new class () implements ProviderDeterminationInterface {
            public function determineProvider(PhoneNumber $phoneNumber): Provider
            {
                return Provider::MTS;
            }
        };

        $smser = new Smser($determination);
        $smser->withCustomProviders(function (ProviderFactoryRegistryInterface $registry) {
            $registry->registerFactory(Provider::MTS, new TestProviderFactory());
        });

        return $smser;
    }

    #[TestDox('Should send SMS and check ability to send')]
    public function test_send_and_can_send(): void
    {
        $smser = $this->createSmser();

        $result = $smser->send('79251234567', 'hi');

        $this->assertInstanceOf(MessageCost::class, $result);
        $this->assertSame(4.0, $result->messageCost); // 2 rub/char * 2 chars
        $this->assertSame(100.0, $result->remainingBalance);

        $this->assertTrue($smser->canSend('79251234567', 'hi'));
    }

    #[TestDox('Should wrap sender with logger when withLogging is used')]
    public function test_with_logging(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $smser = $this->createSmser()->withLogging($logger);

        $sender = (function () {
            return $this->getSender();
        })->call($smser);

        $this->assertInstanceOf(SenderWithLoggerService::class, $sender);

        $result = $smser->send('79251234567', 'msg');
        $this->assertInstanceOf(MessageCost::class, $result);
    }

    #[TestDox('Should wrap sender with retries when withRetries is used')]
    public function test_with_retries(): void
    {
        $smser = $this->createSmser()->withRetries(2, 0);

        $sender = (function () {
            return $this->getSender();
        })->call($smser);

        $this->assertInstanceOf(SenderWithRetriesService::class, $sender);

        $result = $smser->send('79251234567', 'ok');
        $this->assertInstanceOf(MessageCost::class, $result);
    }

    #[TestDox('Should validate retry arguments')]
    public function test_with_retries_validation(): void
    {
        $smser = $this->createSmser();

        $this->expectException(InvalidArgument::class);
        $smser->withRetries(0);
    }

    #[TestDox('Should validate retry delay argument')]
    public function test_with_retries_delay_validation(): void
    {
        $smser = $this->createSmser();

        $this->expectException(InvalidArgument::class);
        $smser->withRetries(1, -1);
    }

    #[TestDox('Should create default sender when no custom providers are set')]
    public function test_create_sender_with_default_factories(): void
    {
        // Service without custom provider configuration
        $determination = new class () implements ProviderDeterminationInterface {
            public function determineProvider(PhoneNumber $phoneNumber): Provider
            {
                return Provider::MTS;
            }
        };

        $smser = new Smser($determination);

        // Use reflection via closure to access protected getSender method
        $sender = (function () {
            return $this->getSender();
        })->call($smser);

        $this->assertInstanceOf(BasicSenderService::class, $sender);

        // Sending should still work with built in factories
        $result = $smser->send('79251234567', 'hello');
        $this->assertSame(10.0, $result->messageCost); // 5 chars * 2 rub
        $this->assertSame(100.0, $result->remainingBalance);
    }
}