<?php

namespace Romandots\Smser\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Exceptions\InsufficientBalance;

class InsufficientBalanceTest extends TestCase
{
    #[TestDox('Should create exception with balance and cost information')]
    public function test_creates_exception_with_balance_and_cost(): void
    {
        $balance = 10.5;
        $cost = 25.0;

        $exception = new InsufficientBalance($balance, $cost);

        $this->assertSame($balance, $exception->balance);
        $this->assertSame($cost, $exception->cost);
        $this->assertInstanceOf(\LogicException::class, $exception);
    }

    #[TestDox('Should generate correct error message')]
    public function test_generates_correct_error_message(): void
    {
        $balance = 5.75;
        $cost = 12.50;

        $exception = new InsufficientBalance($balance, $cost);

        $expectedMessage = "Insufficient balance: {$balance}. Message cost: {$cost}";
        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    #[TestDox('Should handle various balance and cost scenarios')]
    #[DataProvider('balanceCostScenariosProvider')]
    public function test_handles_various_scenarios(float $balance, float $cost, string $expectedMessage): void
    {
        $exception = new InsufficientBalance($balance, $cost);

        $this->assertSame($balance, $exception->balance);
        $this->assertSame($cost, $exception->cost);
        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public static function balanceCostScenariosProvider(): array
    {
        return [
            'Normal scenario' => [
                10.0, 15.0,
                'Insufficient balance: 10. Message cost: 15'
            ],
            'Zero balance' => [
                0.0, 5.0,
                'Insufficient balance: 0. Message cost: 5'
            ],
            'Small amounts' => [
                0.01, 0.02,
                'Insufficient balance: 0.01. Message cost: 0.02'
            ],
            'Large amounts' => [
                999.99, 1000.01,
                'Insufficient balance: 999.99. Message cost: 1000.01'
            ],
            'High precision' => [
                12.345, 12.346,
                'Insufficient balance: 12.345. Message cost: 12.346'
            ],
        ];
    }

    #[TestDox('Should be throwable and catchable')]
    public function test_is_throwable_and_catchable(): void
    {
        $balance = 5.0;
        $cost = 10.0;

        try {
            throw new InsufficientBalance($balance, $cost);
            $this->fail('Exception should have been thrown');
        } catch (InsufficientBalance $e) {
            $this->assertSame($balance, $e->balance);
            $this->assertSame($cost, $e->cost);
            $this->assertStringContainsString('Insufficient balance', $e->getMessage());
        }
    }

    #[TestDox('Should be catchable as parent exception types')]
    public function test_is_catchable_as_parent_types(): void
    {
        $exception = new InsufficientBalance(1.0, 2.0);

        $this->assertInstanceOf(\LogicException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    #[TestDox('Should provide readonly access to properties')]
    public function test_provides_readonly_access_to_properties(): void
    {
        $balance = 15.25;
        $cost = 20.75;

        $exception = new InsufficientBalance($balance, $cost);

        // Свойства должны быть readonly
        $this->assertSame($balance, $exception->balance);
        $this->assertSame($cost, $exception->cost);

        // Проверяем типы
        $this->assertIsFloat($exception->balance);
        $this->assertIsFloat($exception->cost);
    }

    #[TestDox('Should handle edge cases with zero and negative values')]
    public function test_handles_edge_cases(): void
    {
        $testCases = [
            'Zero balance, positive cost' => [0.0, 5.0],
            'Negative balance (debt)' => [-10.0, 5.0],
            'Positive balance, zero cost' => [10.0, 0.0],
            'Both negative' => [-5.0, -2.0],
            'Very small values' => [0.001, 0.002],
        ];

        foreach ($testCases as $caseName => [$balance, $cost]) {
            $exception = new InsufficientBalance($balance, $cost);

            $this->assertSame($balance, $exception->balance, "Failed for case: {$caseName}");
            $this->assertSame($cost, $exception->cost, "Failed for case: {$caseName}");

            $expectedMessage = "Insufficient balance: {$balance}. Message cost: {$cost}";
            $this->assertSame($expectedMessage, $exception->getMessage(), "Failed for case: {$caseName}");
        }
    }

    #[TestDox('Should maintain exception context and stack trace')]
    public function test_maintains_exception_context(): void
    {
        $exception = new InsufficientBalance(5.0, 10.0);

        // Проверяем стандартные свойства исключения
        $this->assertNotEmpty($exception->getFile());
        $this->assertIsInt($exception->getLine());
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    #[TestDox('Should work in business logic context')]
    public function test_works_in_business_context(): void
    {
        // Имитация бизнес-логики
        $currentBalance = 15.0;
        $messageCost = 20.0;

        $canSend = $this->checkIfCanSendMessage($currentBalance, $messageCost);

        $this->assertFalse($canSend);
    }

    /**
     * Имитация метода проверки возможности отправки
     */
    private function checkIfCanSendMessage(float $balance, float $cost): bool
    {
        try {
            if ($balance < $cost) {
                throw new InsufficientBalance($balance, $cost);
            }
            return true;
        } catch (InsufficientBalance $e) {
            // Логируем ошибку и возвращаем false
            $this->assertInstanceOf(InsufficientBalance::class, $e);
            return false;
        }
    }

    #[TestDox('Should be serializable for logging purposes')]
    public function test_is_serializable(): void
    {
        $exception = new InsufficientBalance(12.34, 56.78);

        // Проверяем, что исключение можно сериализовать (для логирования)
        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(InsufficientBalance::class, $unserialized);
        $this->assertSame(12.34, $unserialized->balance);
        $this->assertSame(56.78, $unserialized->cost);
        $this->assertSame($exception->getMessage(), $unserialized->getMessage());
    }
}