<?php

namespace Romandots\Smser\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\TestDox;
use Romandots\Smser\Contracts\SenderServiceInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Services\SenderServiceDecorator;
use Romandots\Smser\Tests\TestCase;

class SenderServiceDecoratorTest extends TestCase
{
    #[TestDox('Should delegate send and canSend to underlying service')]
    public function test_delegates_calls(): void
    {
        $sender = $this->createMock(SenderServiceInterface::class);
        $decorator = new class($sender) extends SenderServiceDecorator {};

        $messageCost = new MessageCost(1.0, 9.0);

        $sender->expects($this->once())
            ->method('send')
            ->with('79251234567', 'test')
            ->willReturn($messageCost);

        $sender->expects($this->once())
            ->method('canSend')
            ->with('79251234567', 'test')
            ->willReturn(true);

        $this->assertSame($messageCost, $decorator->send('79251234567', 'test'));
        $this->assertTrue($decorator->canSend('79251234567', 'test'));
    }
}
