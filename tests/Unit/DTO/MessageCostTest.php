<?php

namespace Romandots\Smser\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\DTO\MessageCost;

class MessageCostTest extends TestCase
{
    #[TestDox('Should create MessageCost with valid values')]
    public function test_creates_message_cost_with_valid_values(): void
    {
        $messageCost = 2.50;
        $remainingBalance = 100.75;

        $cost = new MessageCost($messageCost, $remainingBalance);

        $this->assertSame($messageCost, $cost->messageCost);
        $this->assertSame($remainingBalance, $cost->remainingBalance);
    }

    #[TestDox('Should handle various cost scenarios')]
    #[DataProvider('costScenariosProvider')]
    public function test_handles_various_cost_scenarios(float $messageCost, float $remainingBalance): void
    {
        $cost = new MessageCost($messageCost, $remainingBalance);

        $this->assertSame($messageCost, $cost->messageCost);
        $this->assertSame($remainingBalance, $cost->remainingBalance);
    }

    public static function costScenariosProvider(): array
    {
        return [
            'Normal cost' => [2.5, 100.0],
            'Cheap SMS' => [1.0, 50.25],
            'Expensive SMS' => [5.99, 200.50],
            'Zero cost (promotional)' => [0.0, 100.0],
            'High balance' => [3.0, 1000.99],
            'Low balance' => [1.5, 5.75],
            'Exact balance match' => [10.0, 10.0],
            'Float precision' => [1.234, 99.876],
        ];
    }

    #[TestDox('Should handle zero values correctly')]
    public function test_handles_zero_values(): void
    {
        $testCases = [
            [0.0, 100.0],  // Бесплатная SMS
            [2.5, 0.0],    // Нулевой баланс после отправки
            [0.0, 0.0],    // Все нули
        ];

        foreach ($testCases as [$messageCost, $remainingBalance]) {
            $cost = new MessageCost($messageCost, $remainingBalance);

            $this->assertSame($messageCost, $cost->messageCost);
            $this->assertSame($remainingBalance, $cost->remainingBalance);
        }
    }

    #[TestDox('Should handle negative values (debt scenarios)')]
    public function test_handles_negative_values(): void
    {
        // В реальности может быть долг или переплата
        $testCases = [
            [-1.0, 100.0],  // Возврат средств
            [2.5, -5.0],    // Долг по балансу
            [-0.5, -10.0],  // Полный долг
        ];

        foreach ($testCases as [$messageCost, $remainingBalance]) {
            $cost = new MessageCost($messageCost, $remainingBalance);

            $this->assertSame($messageCost, $cost->messageCost);
            $this->assertSame($remainingBalance, $cost->remainingBalance);
        }
    }

    #[TestDox('Should preserve float precision')]
    public function test_preserves_float_precision(): void
    {
        $preciseCost = 1.234567;
        $preciseBalance = 99.876543;

        $cost = new MessageCost($preciseCost, $preciseBalance);

        $this->assertSame($preciseCost, $cost->messageCost);
        $this->assertSame($preciseBalance, $cost->remainingBalance);
    }

    #[TestDox('Should be comparable by content')]
    public function test_is_comparable_by_content(): void
    {
        $cost1 = new MessageCost(2.5, 100.0);
        $cost2 = new MessageCost(2.5, 100.0);
        $cost3 = new MessageCost(3.0, 100.0); // Разная стоимость

        // Одинаковые значения должны быть равны
        $this->assertEquals($cost1, $cost2);
        $this->assertNotSame($cost1, $cost2); // Но разные объекты

        // Разные значения не должны быть равны
        $this->assertNotEquals($cost1, $cost3);
    }

    #[TestDox('Should work with integer values converted to float')]
    public function test_works_with_integer_input(): void
    {
        $cost = new MessageCost(2, 100); // int values

        $this->assertSame(2.0, $cost->messageCost);
        $this->assertSame(100.0, $cost->remainingBalance);
    }

    #[TestDox('Should maintain type consistency')]
    public function test_maintains_type_consistency(): void
    {
        $cost = new MessageCost(2.5, 100.75);

        // Проверяем, что типы правильные
        $this->assertIsFloat($cost->messageCost);
        $this->assertIsFloat($cost->remainingBalance);

        // И значения корректные
        $this->assertTrue($cost->messageCost > 0);
        $this->assertTrue($cost->remainingBalance > 0);
    }

    #[TestDox('Should handle business logic scenarios')]
    public function test_handles_business_scenarios(): void
    {
        // Сценарий: дорогое сообщение на низком балансе
        $expensiveMessage = new MessageCost(15.0, 2.5);
        $this->assertTrue($expensiveMessage->messageCost > $expensiveMessage->remainingBalance);

        // Сценарий: дешевое сообщение на высоком балансе
        $cheapMessage = new MessageCost(1.0, 500.0);
        $this->assertTrue($cheapMessage->messageCost < $cheapMessage->remainingBalance);

        // Сценарий: точное списание всего баланса
        $exactMatch = new MessageCost(25.0, 0.0);
        $this->assertSame(0.0, $exactMatch->remainingBalance);
    }
}