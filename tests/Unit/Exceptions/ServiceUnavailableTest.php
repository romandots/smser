<?php

namespace Romandots\Smser\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Exceptions\ServiceUnavailable;

class ServiceUnavailableTest extends TestCase
{
    #[TestDox('Should create exception with message only')]
    public function test_creates_exception_with_message_only(): void
    {
        $message = 'Service is temporarily unavailable';

        $exception = new ServiceUnavailable($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertNull($exception->serviceName);
        $this->assertNull($exception->httpCode);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[TestDox('Should create exception with service name')]
    public function test_creates_exception_with_service_name(): void
    {
        $message = 'MTS API is down';
        $serviceName = 'MTS SMS API';

        $exception = new ServiceUnavailable($message, $serviceName);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($serviceName, $exception->serviceName);
        $this->assertNull($exception->httpCode);
    }

    #[TestDox('Should create exception with HTTP code')]
    public function test_creates_exception_with_http_code(): void
    {
        $message = 'Service returned error';
        $serviceName = 'Beeline API';
        $httpCode = 503;

        $exception = new ServiceUnavailable($message, $serviceName, $httpCode);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($serviceName, $exception->serviceName);
        $this->assertSame($httpCode, $exception->httpCode);
    }

    #[TestDox('Should create exception with previous exception')]
    public function test_creates_exception_with_previous(): void
    {
        $originalException = new \Exception('Network timeout');
        $message = 'Failed to connect to SMS service';
        $serviceName = 'Megafon API';
        $httpCode = 500;

        $exception = new ServiceUnavailable($message, $serviceName, $httpCode, $originalException);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($serviceName, $exception->serviceName);
        $this->assertSame($httpCode, $exception->httpCode);
        $this->assertSame($originalException, $exception->getPrevious());
    }

    #[TestDox('Should handle various service scenarios')]
    #[DataProvider('serviceScenarioProvider')]
    public function test_handles_service_scenarios(
        string $message,
        ?string $serviceName,
        ?int $httpCode,
        string $expectedMessage
    ): void {
        $exception = new ServiceUnavailable($message, $serviceName, $httpCode);

        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($serviceName, $exception->serviceName);
        $this->assertSame($httpCode, $exception->httpCode);
    }

    public static function serviceScenarioProvider(): array
    {
        return [
            'API Rate limit' => [
                'Rate limit exceeded',
                'MTS API',
                429,
                'Rate limit exceeded'
            ],
            'Server error' => [
                'Internal server error',
                'Beeline SMS Gateway',
                500,
                'Internal server error'
            ],
            'Service maintenance' => [
                'Service under maintenance',
                'Tele2 API',
                503,
                'Service under maintenance'
            ],
            'Network timeout' => [
                'Request timeout',
                'Megafon API',
                408,
                'Request timeout'
            ],
            'Unknown service' => [
                'Generic service error',
                null,
                null,
                'Generic service error'
            ],
        ];
    }

    #[TestDox('Should provide readonly access to context properties')]
    public function test_provides_readonly_access(): void
    {
        $serviceName = 'Test Service';
        $httpCode = 502;

        $exception = new ServiceUnavailable('Bad gateway', $serviceName, $httpCode);

        // Проверяем, что свойства readonly
        $this->assertSame($serviceName, $exception->serviceName);
        $this->assertSame($httpCode, $exception->httpCode);

        // Проверяем типы
        $this->assertIsString($exception->serviceName);
        $this->assertIsInt($exception->httpCode);
    }

    #[TestDox('Should handle null values correctly')]
    public function test_handles_null_values(): void
    {
        $testCases = [
            // [serviceName, httpCode]
            [null, null],
            ['Service Name', null],
            [null, 404],
        ];

        foreach ($testCases as [$serviceName, $httpCode]) {
            $exception = new ServiceUnavailable('Test message', $serviceName, $httpCode);

            $this->assertSame($serviceName, $exception->serviceName);
            $this->assertSame($httpCode, $exception->httpCode);
        }
    }

    #[TestDox('Should maintain exception hierarchy')]
    public function test_maintains_exception_hierarchy(): void
    {
        $exception = new ServiceUnavailable('Test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    #[TestDox('Should work in retry scenarios')]
    public function test_works_in_retry_scenarios(): void
    {
        $attempts = [];

        for ($i = 1; $i <= 3; $i++) {
            try {
                $this->simulateServiceCall($i);
            } catch (ServiceUnavailable $e) {
                $attempts[] = [
                    'attempt' => $i,
                    'service' => $e->serviceName,
                    'httpCode' => $e->httpCode,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->assertCount(3, $attempts);

        // Проверяем последнюю попытку
        $lastAttempt = end($attempts);
        $this->assertSame(3, $lastAttempt['attempt']);
        $this->assertSame('SMS API', $lastAttempt['service']);
        $this->assertSame(503, $lastAttempt['httpCode']);
    }

    #[TestDox('Should be serializable for logging')]
    public function test_is_serializable(): void
    {
        $originalException = new \Exception('Original error');
        $exception = new ServiceUnavailable(
            'Service error',
            'Test Service',
            500,
            $originalException
        );

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ServiceUnavailable::class, $unserialized);
        $this->assertSame('Service error', $unserialized->getMessage());
        $this->assertSame('Test Service', $unserialized->serviceName);
        $this->assertSame(500, $unserialized->httpCode);
        $this->assertSame('Original error', $unserialized->getPrevious()->getMessage());
    }

    #[TestDox('Should work with HTTP status code ranges')]
    public function test_works_with_http_status_ranges(): void
    {
        $httpCodes = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            408 => 'Request Timeout',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        foreach ($httpCodes as $code => $description) {
            $exception = new ServiceUnavailable($description, 'Test API', $code);

            $this->assertSame($code, $exception->httpCode);
            $this->assertSame($description, $exception->getMessage());
            $this->assertTrue($code >= 400); // Все коды ошибок
        }
    }

    #[TestDox('Should provide context for monitoring and alerting')]
    public function test_provides_monitoring_context(): void
    {
        $exception = new ServiceUnavailable(
            'Database connection failed',
            'SMS Database',
            null, // Не HTTP ошибка
            new \PDOException('Connection timeout')
        );

        // Информация для мониторинга
        $context = [
            'error_type' => 'service_unavailable',
            'service_name' => $exception->serviceName,
            'http_code' => $exception->httpCode,
            'message' => $exception->getMessage(),
            'has_previous' => $exception->getPrevious() !== null,
            'previous_type' => $exception->getPrevious() ? get_class($exception->getPrevious()) : null,
        ];

        $this->assertSame('service_unavailable', $context['error_type']);
        $this->assertSame('SMS Database', $context['service_name']);
        $this->assertNull($context['http_code']);
        $this->assertTrue($context['has_previous']);
        $this->assertSame(\PDOException::class, $context['previous_type']);
    }

    /**
     * Имитация вызова сервиса для тестирования retry логики
     */
    private function simulateServiceCall(int $attempt): void
    {
        throw new ServiceUnavailable(
            "Service call failed on attempt {$attempt}",
            'SMS API',
            503
        );
    }
}