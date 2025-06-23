<?php

namespace Romandots\Smser\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\TestDox;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Exceptions\UnknownProvider;
use Romandots\Smser\Exceptions\ServiceUnavailable;

class CustomExceptionsTest extends TestCase
{
    #[TestDox('InvalidArgument should extend InvalidArgumentException')]
    public function test_invalid_argument_inheritance(): void
    {
        $exception = new InvalidArgument('Test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\LogicException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    #[TestDox('InvalidArgument should work with custom messages')]
    public function test_invalid_argument_custom_messages(): void
    {
        $messages = [
            'Phone number cannot be empty',
            'Message cannot be empty',
            'Max attempts must be at least 1',
            'Retry delay cannot be negative',
        ];

        foreach ($messages as $message) {
            $exception = new InvalidArgument($message);

            $this->assertSame($message, $exception->getMessage());
            $this->assertInstanceOf(InvalidArgument::class, $exception);
        }
    }

    #[TestDox('UnknownProvider should extend LogicException')]
    public function test_unknown_provider_inheritance(): void
    {
        $exception = new UnknownProvider('Test provider error');

        $this->assertInstanceOf(\LogicException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test provider error', $exception->getMessage());
    }

    #[TestDox('UnknownProvider should work with provider-specific messages')]
    public function test_unknown_provider_messages(): void
    {
        $testCases = [
            'Unknown provider for phone number 79251234567',
            'Provider МТС not implemented',
            'Provider Билайн not implemented',
            'Provider factory not found for Мегафон',
        ];

        foreach ($testCases as $message) {
            $exception = new UnknownProvider($message);

            $this->assertSame($message, $exception->getMessage());
            $this->assertInstanceOf(UnknownProvider::class, $exception);
        }
    }

    #[TestDox('ServiceUnavailable should extend RuntimeException')]
    public function test_service_unavailable_inheritance(): void
    {
        $exception = new ServiceUnavailable('Service is down');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Service is down', $exception->getMessage());
        $this->assertNull($exception->serviceName);
        $this->assertNull($exception->httpCode);
    }

    #[TestDox('ServiceUnavailable should work with enhanced constructor')]
    public function test_service_unavailable_enhanced_constructor(): void
    {
        $testCases = [
            // [message, serviceName, httpCode]
            ['SMS service temporarily unavailable', null, null],
            ['Provider API is down', 'MTS API', null],
            ['Network timeout occurred', 'Beeline Gateway', 408],
            ['Rate limit exceeded', 'Megafon API', 429],
        ];

        foreach ($testCases as [$message, $serviceName, $httpCode]) {
            $exception = new ServiceUnavailable($message, $serviceName, $httpCode);

            $this->assertSame($message, $exception->getMessage());
            $this->assertSame($serviceName, $exception->serviceName);
            $this->assertSame($httpCode, $exception->httpCode);
            $this->assertInstanceOf(ServiceUnavailable::class, $exception);
        }
    }

    #[TestDox('All exceptions should be throwable and catchable')]
    public function test_all_exceptions_throwable(): void
    {
        $exceptions = [
            InvalidArgument::class => 'Invalid argument',
            UnknownProvider::class => 'Unknown provider',
            ServiceUnavailable::class => 'Service unavailable',
        ];

        foreach ($exceptions as $exceptionClass => $message) {
            try {
                throw new $exceptionClass($message);
                $this->fail("Exception {$exceptionClass} should have been thrown");
            } catch (\Throwable $e) {
                $this->assertInstanceOf($exceptionClass, $e);
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    #[TestDox('Exceptions should maintain proper exception hierarchy')]
    public function test_exception_hierarchy(): void
    {
        // InvalidArgument -> InvalidArgumentException -> LogicException
        $invalidArg = new InvalidArgument('test');
        $this->assertTrue($this->canCatchAs($invalidArg, \InvalidArgumentException::class));
        $this->assertTrue($this->canCatchAs($invalidArg, \LogicException::class));
        $this->assertFalse($this->canCatchAs($invalidArg, \RuntimeException::class));

        // UnknownProvider -> LogicException
        $unknownProvider = new UnknownProvider('test');
        $this->assertTrue($this->canCatchAs($unknownProvider, \LogicException::class));
        $this->assertFalse($this->canCatchAs($unknownProvider, \RuntimeException::class));

        // ServiceUnavailable -> RuntimeException
        $serviceUnavailable = new ServiceUnavailable('test');
        $this->assertTrue($this->canCatchAs($serviceUnavailable, \RuntimeException::class));
        $this->assertFalse($this->canCatchAs($serviceUnavailable, \LogicException::class));

        // Проверяем дополнительные свойства
        $this->assertNull($serviceUnavailable->serviceName);
        $this->assertNull($serviceUnavailable->httpCode);
    }

    #[TestDox('Exceptions should work with error codes and enhanced constructors')]
    public function test_exceptions_with_error_codes(): void
    {
        // InvalidArgument и UnknownProvider - стандартные конструкторы
        $invalidArg = new InvalidArgument('Invalid phone', 1001);
        $this->assertSame('Invalid phone', $invalidArg->getMessage());
        $this->assertSame(1001, $invalidArg->getCode());

        $unknownProvider = new UnknownProvider('Provider not found', 2001);
        $this->assertSame('Provider not found', $unknownProvider->getMessage());
        $this->assertSame(2001, $unknownProvider->getCode());

        // ServiceUnavailable - расширенный конструктор
        $serviceUnavailable = new ServiceUnavailable('API down', 'MTS API', 503);
        $this->assertSame('API down', $serviceUnavailable->getMessage());
        $this->assertSame('MTS API', $serviceUnavailable->serviceName);
        $this->assertSame(503, $serviceUnavailable->httpCode);
        $this->assertSame(0, $serviceUnavailable->getCode()); // Код ошибки всегда 0 для ServiceUnavailable
    }

    #[TestDox('Exceptions should work with previous exceptions')]
    public function test_exceptions_with_previous(): void
    {
        $originalException = new \Exception('Original error');

        // InvalidArgument и UnknownProvider - стандартные конструкторы
        $invalidArg = new InvalidArgument('Wrapped error', 0, $originalException);
        $this->assertSame($originalException, $invalidArg->getPrevious());

        $unknownProvider = new UnknownProvider('Wrapped error', 0, $originalException);
        $this->assertSame($originalException, $unknownProvider->getPrevious());

        // ServiceUnavailable - расширенный конструктор
        $serviceUnavailable = new ServiceUnavailable(
            'Wrapped error',
            'Test Service',
            500,
            $originalException
        );
        $this->assertSame($originalException, $serviceUnavailable->getPrevious());
        $this->assertSame('Test Service', $serviceUnavailable->serviceName);
        $this->assertSame(500, $serviceUnavailable->httpCode);
    }

    /**
     * Helper method to check if exception can be caught as specific type
     */
    private function canCatchAs(\Throwable $exception, string $catchType): bool
    {
        try {
            throw $exception;
        } catch (\Throwable $e) {
            return $e instanceof $catchType;
        }
    }
}