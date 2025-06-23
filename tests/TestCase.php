<?php

namespace Romandots\Smser\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    public function createMockWithMethods(string $className, array $methods): object
    {
        $mock = $this->createMock($className);

        foreach ($methods as $method => $returnValue) {
            $mock->method($method)->willReturn($returnValue);
        }

        return $mock;
    }

    public function expectExceptionWithMessage(string $exceptionClassName, string $message): void
    {
        $this->expectException($exceptionClassName);
        $this->expectExceptionMessage($message);
    }
}