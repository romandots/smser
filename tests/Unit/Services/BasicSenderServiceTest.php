<?php

namespace Romandots\Smser\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Romandots\Smser\Contracts\BalanceCheckerInterface;
use Romandots\Smser\Contracts\CostCalculatorInterface;
use Romandots\Smser\Contracts\ProviderDeterminationInterface;
use Romandots\Smser\Contracts\ProviderFactoryInterface;
use Romandots\Smser\Contracts\ProviderFactoryResolverInterface;
use Romandots\Smser\Contracts\SmsSenderInterface;
use Romandots\Smser\DTO\MessageCost;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\ServiceUnavailable;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Services\BasicSenderService;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Value\Provider;

class BasicSenderServiceTest extends TestCase
{
    private BasicSenderService $service;
    private MockObject|ProviderDeterminationInterface $providerDetermination;
    private MockObject|ProviderFactoryResolverInterface $factoryResolver;
    private MockObject|ProviderFactoryInterface $providerFactory;
    private MockObject|SmsSenderInterface $smsSender;
    private MockObject|BalanceCheckerInterface $balanceChecker;
    private MockObject|CostCalculatorInterface $costCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем все необходимые моки
        $this->providerDetermination = $this->createMock(ProviderDeterminationInterface::class);
        $this->factoryResolver = $this->createMock(ProviderFactoryResolverInterface::class);
        $this->providerFactory = $this->createMock(ProviderFactoryInterface::class);
        $this->smsSender = $this->createMock(SmsSenderInterface::class);
        $this->balanceChecker = $this->createMock(BalanceCheckerInterface::class);
        $this->costCalculator = $this->createMock(CostCalculatorInterface::class);

        // Настраиваем базовые связи между моками
        $this->setupBasicMockChain();

        // Настраиваем дефолтное поведение для провайдера
        // Возвращаем реальный enum вместо мока
        $this->providerDetermination
            ->method('determineProvider')
            ->willReturn(Provider::MTS); // Возвращаем реальный enum!

        // Создаем тестируемый сервис
        $this->service = new BasicSenderService(
            $this->providerDetermination,
            $this->factoryResolver
        );
    }

    #[TestDox('Should successfully send SMS when all conditions are met')]
    public function test_successful_sms_sending(): void
    {
        // Arrange: Настраиваем успешный сценарий
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::MTS;
        $balance = 100.0;
        $cost = 2.5;
        $finalBalance = 97.5;

        // Явно настраиваем все моки
        $this->providerDetermination
            ->method('determineProvider')
            ->willReturn($provider);

        $this->factoryResolver
            ->method('getProviderFactory')
            ->with($provider)
            ->willReturn($this->providerFactory);

        $this->balanceChecker
            ->method('checkBalance')
            ->willReturnOnConsecutiveCalls($balance, $finalBalance);

        $this->costCalculator
            ->method('calculateMessageCost')
            ->with($message)
            ->willReturn($cost);

        $this->smsSender
            ->method('send')
            ->willReturn($cost);

        // Act: Выполняем отправку
        $result = $this->service->send($phone, $message);

        // Assert: Проверяем результат
        $this->assertInstanceOf(MessageCost::class, $result);
        var_dump($cost, $result);exit();
        $this->assertSame($cost, $result->messageCost);
        $this->assertSame($finalBalance, $result->remainingBalance);
    }

    #[TestDox('Should throw InsufficientBalance when balance is less than cost')]
    public function test_insufficient_balance_exception(): void
    {
        // Arrange
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::BEELINE;
        $balance = 1.0;    // Недостаточно
        $cost = 5.0;       // Дорого

        // Переопределяем дефолтное поведение моков для этого теста
        $this->providerDetermination
            ->expects($this->atLeastOnce())
            ->method('determineProvider')
            ->willReturn($provider);

        $this->factoryResolver
            ->expects($this->atLeastOnce())
            ->method('getProviderFactory')
            ->with($provider)
            ->willReturn($this->providerFactory);

        $this->balanceChecker
            ->expects($this->atLeastOnce())
            ->method('checkBalance')
            ->willReturn($balance);

        $this->costCalculator
            ->expects($this->atLeastOnce())
            ->method('calculateMessageCost')
            ->with($message)
            ->willReturn($cost);

        // Отправка НЕ должна происходить при недостатке баланса
        $this->smsSender->expects($this->never())->method('send');

        // Assert & Act
        $this->expectExceptionWithMessage(
            InsufficientBalance::class,
            "Insufficient balance: {$balance}. Message cost: {$cost}"
        );

        $this->service->send($phone, $message);
    }

    #[TestDox('Should throw InvalidArgument for invalid phone number')]
    public function test_invalid_phone_number(): void
    {
        $invalidPhone = 'not-a-phone-number';
        $message = 'Test message';

        $this->expectException(InvalidArgument::class);

        $this->service->send($invalidPhone, $message);
    }

    #[TestDox('Should throw InvalidArgument for empty message')]
    public function test_empty_message(): void
    {
        $phone = '79251234567';
        $emptyMessage = '';

        // Для этого теста не важно, что возвращает провайдер
        // InvalidArgument должен быть брошен в конструкторе Message
        $this->expectException(InvalidArgument::class);

        $this->service->send($phone, $emptyMessage);
    }

    #[TestDox('Should throw InvalidArgument for whitespace-only message')]
    public function test_whitespace_only_message(): void
    {
        $phone = '79251234567';
        $whitespaceMessage = '   ';

        // trim() в Message сделает строку пустой
        $this->expectException(InvalidArgument::class);

        $this->service->send($phone, $whitespaceMessage);
    }

    #[TestDox('Should throw UnknownProvider when provider cannot be determined')]
    public function test_unknown_provider_exception(): void
    {
        $phone = '79251234567';
        $message = 'Test message';

        // Переопределяем дефолтное поведение
        $this->providerDetermination
            ->expects($this->once())
            ->method('determineProvider')
            ->willThrowException(new UnknownProvider('Unknown provider for phone number'));

        $this->expectException(UnknownProvider::class);

        $this->service->send($phone, $message);
    }

    #[TestDox('Should throw UnknownProvider when factory is not registered')]
    public function test_unregistered_factory_exception(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::TELE2;

        // Провайдер определяется, но фабрика не зарегистрирована
        $this->setupProviderDetermination($phone, $provider);

        $this->factoryResolver
            ->expects($this->once())
            ->method('getProviderFactory')
            ->with($provider)
            ->willThrowException(new UnknownProvider("Provider {$provider->value} has no factory"));

        $this->expectException(UnknownProvider::class);

        $this->service->send($phone, $message);
    }

    #[TestDox('Should propagate ServiceUnavailable from balance checker')]
    public function test_service_unavailable_during_balance_check(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::MEGAFON;

        $this->setupProviderDetermination($phone, $provider);
        $this->setupFactoryResolver($provider);

        // Сервис проверки баланса недоступен
        $this->balanceChecker
            ->expects($this->once())
            ->method('checkBalance')
            ->willThrowException(new ServiceUnavailable('Balance service down', 'Megafon API', 503));

        $this->expectException(ServiceUnavailable::class);

        $this->service->send($phone, $message);
    }

    #[TestDox('Should propagate ServiceUnavailable from SMS sender')]
    public function test_service_unavailable_during_sending(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::MTS;
        $balance = 100.0;
        $cost = 2.0;

        // Проверка баланса проходит успешно
        $this->setupProviderDetermination($phone, $provider);
        $this->setupFactoryResolver($provider);
        $this->setupBalance($balance);
        $this->setupCost($message, $cost);

        // Но отправка SMS падает
        $this->smsSender
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new ServiceUnavailable('SMS gateway down', 'MTS Gateway', 502));

        $this->expectException(ServiceUnavailable::class);

        $this->service->send($phone, $message);
    }

    #[TestDox('Should return true when canSend and balance is sufficient')]
    public function test_can_send_with_sufficient_balance(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::BEELINE;
        $balance = 50.0;
        $cost = 3.0;

        $this->setupProviderDetermination($phone, $provider);
        $this->setupFactoryResolver($provider);
        $this->setupBalance($balance);
        $this->setupCost($message, $cost);

        $result = $this->service->canSend($phone, $message);

        $this->assertTrue($result);
    }

    #[TestDox('Should return false when canSend and balance is insufficient')]
    public function test_can_send_with_insufficient_balance(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::TELE2;
        $balance = 1.0;   // Мало
        $cost = 10.0;     // Дорого

        $this->setupProviderDetermination($phone, $provider);
        $this->setupFactoryResolver($provider);
        $this->setupBalance($balance);
        $this->setupCost($message, $cost);

        $result = $this->service->canSend($phone, $message);

        $this->assertFalse($result);
    }

    #[TestDox('Should propagate exceptions from canSend except InsufficientBalance')]
    public function test_can_send_propagates_other_exceptions(): void
    {
        $phone = '79251234567';
        $message = 'Test message';

        // Переопределяем дефолтное поведение
        $this->providerDetermination
            ->method('determineProvider')
            ->willThrowException(new UnknownProvider('Provider not found'));

        $this->expectException(UnknownProvider::class);

        $this->service->canSend($phone, $message);
    }

    #[TestDox('Should normalize phone numbers correctly')]
    public function test_phone_number_normalization(): void
    {
        $phoneVariants = [
            '9251234567',           // Без кода страны
            '79251234567',          // С кодом 7
            '8(925)123-45-67',      // Со старым кодом 8
            '+7 925 123 45 67',     // Международный формат
        ];

        $message = 'Test message';
        $provider = Provider::MTS;

        foreach ($phoneVariants as $phoneInput) {
            // Все варианты должны нормализоваться к 79251234567
            $this->providerDetermination
                ->expects($this->once())
                ->method('determineProvider')
                ->with($this->callback(function (PhoneNumber $phoneNumber) {
                    return $phoneNumber->value === '79251234567';
                }))
                ->willReturn($provider);

            $this->setupFactoryResolver($provider);
            $this->setupBalance(100.0);
            $this->setupCost($message, 2.0);

            $result = $this->service->canSend($phoneInput, $message);
            $this->assertTrue($result, "Failed for phone input: {$phoneInput}");

            // Сбрасываем моки для следующей итерации
            $this->setUp();
        }
    }

    #[TestDox('Should call all components in correct order during send')]
    public function test_component_call_order_during_send(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::MTS;

        // Настраиваем моки с ожиданиями порядка вызовов
        $this->providerDetermination
            ->expects($this->exactly(2)) // В checkBalance и в buildSms для send
            ->method('determineProvider')
            ->willReturn($provider);

        $this->factoryResolver
            ->expects($this->exactly(2)) // В checkBalance и в send
            ->method('getProviderFactory')
            ->willReturn($this->providerFactory);

        $this->balanceChecker
            ->expects($this->exactly(2)) // В checkBalance и после send
            ->method('checkBalance')
            ->willReturnOnConsecutiveCalls(100.0, 97.5);

        $this->costCalculator
            ->expects($this->once()) // Только в checkBalance
            ->method('calculateMessageCost')
            ->willReturn(2.5);

        $this->smsSender
            ->expects($this->once()) // В send
            ->method('send')
            ->willReturn(2.5);

        $result = $this->service->send($phone, $message);

        $this->assertInstanceOf(MessageCost::class, $result);
    }

    /**
     * Настраивает базовые связи между моками с дефолтными значениями
     */
    private function setupBasicMockChain(): void
    {
        // Резолвер возвращает фабрику по умолчанию
        $this->factoryResolver
            ->method('getProviderFactory')
            ->willReturn($this->providerFactory);

        // Фабрика возвращает нужные компоненты
        $this->providerFactory
            ->method('sender')
            ->willReturn($this->smsSender);

        $this->providerFactory
            ->method('balanceChecker')
            ->willReturn($this->balanceChecker);

        $this->providerFactory
            ->method('costCalculator')
            ->willReturn($this->costCalculator);

        // Дефолтные значения для избежания ошибок
        $this->balanceChecker
            ->method('checkBalance')
            ->willReturn(100.0); // Достаточный баланс по умолчанию

        $this->costCalculator
            ->method('calculateMessageCost')
            ->willReturn(1.0); // Низкая стоимость по умолчанию

        $this->smsSender
            ->method('send')
            ->willReturn(1.0); // Возвращаем стоимость отправки
    }

    /**
     * Настраивает успешный сценарий отправки SMS
     */
    private function setupSuccessfulSendingScenario(
        string $phone,
        Provider $provider,
        float $balance,
        float $cost,
        float $finalBalance
    ): void {
        $this->setupProviderDetermination($phone, $provider);
        $this->setupFactoryResolver($provider);
        $this->setupBalance($balance, $finalBalance);
        $this->setupCostAndSending($cost);
    }

    /**
     * Настраивает определение провайдера
     */
    private function setupProviderDetermination(string $phone, Provider $provider): void
    {
        $this->providerDetermination
            ->method('determineProvider')
            ->with($this->callback(function (PhoneNumber $phoneNumber) use ($phone) {
                // Проверяем, что номер правильно нормализован
                return $phoneNumber->value === (new PhoneNumber($phone))->value;
            }))
            ->willReturn($provider);
    }

    /**
     * Настраивает резолвер фабрик
     */
    private function setupFactoryResolver(Provider $provider): void
    {
        $this->factoryResolver
            ->method('getProviderFactory')
            ->with($provider)
            ->willReturn($this->providerFactory);
    }

    /**
     * Настраивает проверку баланса
     */
    private function setupBalance(float $initialBalance, ?float $finalBalance = null): void
    {
        if ($finalBalance !== null) {
            // Два вызова: в checkBalance и после send
            $this->balanceChecker
                ->method('checkBalance')
                ->willReturnOnConsecutiveCalls($initialBalance, $finalBalance);
        } else {
            // Один вызов: только в checkBalance
            $this->balanceChecker
                ->method('checkBalance')
                ->willReturn($initialBalance);
        }
    }

    /**
     * Настраивает расчет стоимости
     */
    private function setupCost(string $message, float $cost): void
    {
        $this->costCalculator
            ->method('calculateMessageCost')
            ->with($message) // ИСПРАВЛЕНО: передаем строку, а не объект Message
            ->willReturn($cost);
    }

    /**
     * Настраивает стоимость и отправку
     */
    private function setupCostAndSending(float $cost): void
    {
        $this->costCalculator
            ->method('calculateMessageCost')
            ->willReturn($cost);

        $this->smsSender
            ->method('send')
            ->willReturn($cost);
    }
}