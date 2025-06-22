<?php

namespace Romandots\Smser\Tests\Unit\Value;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Romandots\Smser\Exceptions\InvalidArgument;
use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Tests\TestCase;

class PhoneNumberTest extends TestCase
{

    #[TestDox("Should create valid phone number with valid 11-digit number starting with 7")]
    public function test_creates_valid_phone_number(): void
    {
        $value = '79991112233';
        $number = new PhoneNumber($value);

        $this->assertSame($value, $number->value);
        $this->assertSame($value, (string)$number);
    }

    #[TestDox("Should normalize 10-digit number by adding country code 7")]
    public function test_normalizes_ten_digit_number(): void
    {
        $phone = new PhoneNumber('9251234567');

        $this->assertSame('79251234567', $phone->value);
    }


    #[TestDox("Should remove non-digit characters before processing")]
    public function test_removes_non_digit_characters(): void
    {
        $testCases = [
            '+7 (925) 123-45-67' => '79251234567',
            '7-925-123-45-67' => '79251234567',
            '7 925 123 45 67' => '79251234567',
            '+7(925)1234567' => '79251234567',
            '8(925)123-45-67' => '79251234567',
        ];

        foreach ($testCases as $input => $expected) {
            $phone = new PhoneNumber($input);
            $this->assertSame($expected, $phone->value, "Failed for input: $input");
        }
    }

    #[TestDox("Test throws exception for empty phone number")]
    public function test_throws_exception_for_empty_phone_number(): void
    {
        $this->expectExceptionWithMessage(InvalidArgument::class, "Phone number cannot be empty");
        new PhoneNumber('');
    }

    #[TestDox("Should throw exception for phone number with only non-digit characters")]
    public function test_throws_exception_for_non_digit_only_input(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Phone number cannot be empty'
        );

        new PhoneNumber('+++---()()()');
    }

    #[TestDox("Should throw exception for phone number shorter than 10 digits")]
    public function test_throws_exception_for_short_phone_number(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Phone number must be 11 digits'
        );

        new PhoneNumber('925123456');
    }

    #[TestDox("Should throw exception for phone number longer than 11 digits")]
    public function test_throws_exception_for_long_phone_number(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Phone number must be 11 digits'
        );

        new PhoneNumber('792512345678');
    }

    #[TestDox("Should throw exception for 11-digit number not starting with 7")]
    public function test_throws_exception_for_invalid_country_code(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Phone number must start with 7'
        );

        new PhoneNumber('19251234567');
    }

    #[TestDox("Should throw exception for 10-digit number starting with 7")]
    public function test_throws_exception_for_ten_digit_starting_with_seven(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Phone number must be 11 digits'
        );

        new PhoneNumber('7925123456');
    }

    #[TestDox("Should handle edge cases with whitespace")]
    public function test_handles_whitespace_edge_cases(): void
    {
        $phone = new PhoneNumber('  79251234567  ');
        $this->assertSame('79251234567', $phone->value);
    }

    #[DataProvider('russianPhoneNumberProvider')]
    #[TestDox("Should handle various Russian phone number formats with \$input")]
    public function test_handles_russian_phone_formats(string $input, string $expected): void
    {
        $phone = new PhoneNumber($input);
        $this->assertSame($expected, $phone->value);
    }

    public static function russianPhoneNumberProvider(): array
    {
        return [
            'МТС Moscow' => ['+7(495)1234567', '74951234567'],
            'Beeline with 8' => ['8-903-123-45-67', '79031234567'], // 8 → 7
            'MegaFon' => ['+7 926 123 45 67', '79261234567'],
            'Tele2' => ['7-952-123-45-67', '79521234567'],
            'Without country code' => ['9251234567', '79251234567'],
            'Old format 8' => ['8(925)1234567', '79251234567'], // 8 → 7
        ];
    }
}
