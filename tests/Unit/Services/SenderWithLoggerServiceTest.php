<?php

namespace Romandots\Smser\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Services\SenderWithLoggerService;
use Romandots\Smser\Tests\TestCase;

class SenderWithLoggerServiceTest extends TestCase
{
    #[TestDox('Should log info on successful sending')]
    public function test_logs_info_on_success(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new SenderWithLoggerService($sender, $logger);

        $messageCost = new MessageCost(2.0, 98.0);

        $sender->expects($this->once())
            ->method('send')
            ->willReturn($messageCost);

        $logger->expects($this->once())
            ->method('info')
            ->with(
                'SMS sent successfully',
                $this->arrayHasKey('phone')
            );

        $result = $service->send('79251234567', 'msg');

        $this->assertSame($messageCost, $result);
    }

    #[TestDox('Should log error and rethrow on failure')]
    public function test_logs_error_on_failure(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new SenderWithLoggerService($sender, $logger);

        $exception = new InsufficientBalance(1.0, 2.0);

        $sender->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('SMS sending failed'));

        $this->expectException(InsufficientBalance::class);

        $service->send('79251234567', 'msg');
    }

    #[TestDox('Should work without logger')]
    public function test_works_without_logger(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $service = new SenderWithLoggerService($sender, null);

        $messageCost = new MessageCost(3.0, 97.0);

        $sender->expects($this->once())
            ->method('send')
            ->willReturn($messageCost);

        $result = $service->send('79251234567', 'msg');

        $this->assertSame($messageCost, $result);
    }
}
