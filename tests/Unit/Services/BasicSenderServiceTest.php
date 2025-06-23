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

        // Create all required mocks
        $this->providerDetermination = $this->createMock(ProviderDeterminationInterface::class);
        $this->factoryResolver = $this->createMock(ProviderFactoryResolverInterface::class);
        $this->providerFactory = $this->createMock(ProviderFactoryInterface::class);
        $this->smsSender = $this->createMock(SmsSenderInterface::class);
        $this->balanceChecker = $this->createMock(BalanceCheckerInterface::class);
        $this->costCalculator = $this->createMock(CostCalculatorInterface::class);

        // Set up the basic mock chain
        $this->setupBasicMockChain();

        // Instantiate the service under test
        $this->service = new BasicSenderService(
            $this->providerDetermination,
            $this->factoryResolver
        );
    }

    #[TestDox('Should successfully send SMS when all conditions are met')]
    public function test_successful_sms_sending(): void
    {
        // Arrange: configure a successful scenario
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::MTS;
        $balance = 100.0;
        $cost = 2.5;
        $finalBalance = 97.5;

        // Explicitly configure all mocks
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

        // Act: send the SMS
        $result = $this->service->send($phone, $message);

        // Assert: verify the result
        $this->assertInstanceOf(MessageCost::class, $result);
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
        $balance = 1.0;    // Not enough funds
        $cost = 5.0;       // Expensive SMS

        // Override the default mock behaviour for this test
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

        // Sending MUST NOT happen with insufficient balance
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

        // Provider result is irrelevant for this test
        $this->providerDetermination
            ->method('determineProvider')
            ->willReturn(Provider::MTS);
        // InvalidArgument will be thrown in the Message constructor
        $this->expectException(InvalidArgument::class);

        $this->service->send($phone, $emptyMessage);
    }

    #[TestDox('Should throw InvalidArgument for whitespace-only message')]
    public function test_whitespace_only_message(): void
    {
        $phone = '79251234567';
        $whitespaceMessage = '   ';

        // Provider result is irrelevant for this test
        $this->providerDetermination
            ->method('determineProvider')
            ->willReturn(Provider::MTS);
        // trim() in Message will make the string empty
        $this->expectException(InvalidArgument::class);

        $this->service->send($phone, $whitespaceMessage);
    }

    #[TestDox('Should throw UnknownProvider when provider cannot be determined')]
    public function test_unknown_provider_exception(): void
    {
        $phone = '79251234567';
        $message = 'Test message';

        // Override the default behaviour
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

        // Provider is determined but its factory is not registered
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

        // Balance check service is unavailable
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

        // Balance check succeeds
        $this->setupProviderDetermination($phone, $provider);
        $this->setupFactoryResolver($provider);
        $this->setupBalance($balance);
        $this->setupCost($message, $cost);

        // But sending SMS fails
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
        $balance = 1.0;   // Low balance
        $cost = 10.0;     // Expensive SMS

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

        // Override the default behaviour
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
            '9251234567',           // Without country code
            '79251234567',          // With country code 7
            '8(925)123-45-67',      // With old prefix 8
            '+7 925 123 45 67',     // International format
        ];

        $message = 'Test message';
        $provider = Provider::MTS;

        foreach ($phoneVariants as $phoneInput) {
            // All variants should normalize to 79251234567
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

            // Reset mocks for the next iteration
            $this->setUp();
        }
    }

    #[TestDox('Should call all components in correct order during send')]
    public function test_component_call_order_during_send(): void
    {
        $phone = '79251234567';
        $message = 'Test message';
        $provider = Provider::MTS;

        // Configure mocks with call order expectations
        $this->providerDetermination
            ->expects($this->exactly(2)) // In checkBalance and buildSms during send
            ->method('determineProvider')
            ->willReturn($provider);

        $this->factoryResolver
            ->expects($this->exactly(2)) // In checkBalance and send
            ->method('getProviderFactory')
            ->willReturn($this->providerFactory);

        $this->balanceChecker
            ->expects($this->exactly(2)) // In checkBalance and after send
            ->method('checkBalance')
            ->willReturnOnConsecutiveCalls(100.0, 97.5);

        $this->costCalculator
            ->expects($this->once()) // Only in checkBalance
            ->method('calculateMessageCost')
            ->willReturn(2.5);

        $this->smsSender
            ->expects($this->once()) // During send
            ->method('send')
            ->willReturn(2.5);

        $result = $this->service->send($phone, $message);

        $this->assertInstanceOf(MessageCost::class, $result);
    }

    /**
     * Set up basic mock relationships with default values
     */
    private function setupBasicMockChain(): void
    {
        // Resolver returns the default factory
        $this->factoryResolver
            ->method('getProviderFactory')
            ->willReturn($this->providerFactory);

        // Factory returns required components
        $this->providerFactory
            ->method('sender')
            ->willReturn($this->smsSender);

        $this->providerFactory
            ->method('balanceChecker')
            ->willReturn($this->balanceChecker);

        $this->providerFactory
            ->method('costCalculator')
            ->willReturn($this->costCalculator);
    }

    /**
     * Configure a successful SMS sending scenario
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
     * Configure provider determination
     */
    private function setupProviderDetermination(string $phone, Provider $provider): void
    {
        $this->providerDetermination
            ->method('determineProvider')
            ->with($this->callback(function (PhoneNumber $phoneNumber) use ($phone) {
                // Verify that the phone number is normalized
                return $phoneNumber->value === (new PhoneNumber($phone))->value;
            }))
            ->willReturn($provider);
    }

    /**
     * Configure the factory resolver
     */
    private function setupFactoryResolver(Provider $provider): void
    {
        $this->factoryResolver
            ->method('getProviderFactory')
            ->with($provider)
            ->willReturn($this->providerFactory);
    }

    /**
     * Configure balance checking
     */
    private function setupBalance(float $initialBalance, ?float $finalBalance = null): void
    {
        if ($finalBalance !== null) {
            // Two calls: in checkBalance and after send
            $this->balanceChecker
                ->method('checkBalance')
                ->willReturnOnConsecutiveCalls($initialBalance, $finalBalance);
        } else {
            // One call: only in checkBalance
            $this->balanceChecker
                ->method('checkBalance')
                ->willReturn($initialBalance);
        }
    }

    /**
     * Configure message cost calculation
     */
    private function setupCost(string $message, float $cost): void
    {
        $this->costCalculator
            ->method('calculateMessageCost')
            ->with($message)
            ->willReturn($cost);
    }

    /**
     * Configure cost calculation and sending
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