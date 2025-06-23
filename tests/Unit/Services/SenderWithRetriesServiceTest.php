<?php

namespace Romandots\Smser\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\Stub\Exception as ExceptionStub;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\ServiceUnavailable;
use Romandots\Smser\Services\SenderWithRetriesService;
use Romandots\Smser\Tests\TestCase;

class SenderWithRetriesServiceTest extends TestCase
{
    #[TestDox('Should retry on ServiceUnavailable and eventually succeed')]
    public function test_retries_and_succeeds(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new SenderWithRetriesService($sender, $logger, 3, 0);

        $messageCost = new MessageCost(1.0, 9.0);

        $sender->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                new ExceptionStub(new ServiceUnavailable('fail')),
                $messageCost
            );

        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('debug');

        $result = $service->send('79251234567', 'msg');

        $this->assertSame($messageCost, $result);
    }

    #[TestDox('Should throw ServiceUnavailable after all attempts fail')]
    public function test_throws_after_all_attempts(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new SenderWithRetriesService($sender, $logger, 2, 0);

        $sender->expects($this->exactly(2))
            ->method('send')
            ->willThrowException(new ServiceUnavailable('down'));

        $logger->expects($this->exactly(2))->method('warning');
        $logger->expects($this->once())->method('debug');

        $this->expectException(ServiceUnavailable::class);

        $service->send('79251234567', 'msg');
    }

    #[TestDox('Should not retry on other exceptions')]
    public function test_does_not_retry_on_other_exceptions(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new SenderWithRetriesService($sender, $logger, 3, 0);

        $sender->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('fail'));

        $logger->expects($this->never())->method('warning');
        $logger->expects($this->never())->method('debug');

        $this->expectException(\RuntimeException::class);

        $service->send('79251234567', 'msg');
    }
}
