<?php

namespace Romandots\Smser\Tests\Unit\Value;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\Message;
use Romandots\Smser\Exceptions\InvalidArgument;

class MessageTest extends TestCase
{
    #[TestDox('Should create message with valid text')]
    public function test_creates_valid_message(): void
    {
        $message = new Message('Hello, world!');

        $this->assertSame('Hello, world!', $message->value);
        $this->assertSame('Hello, world!', (string) $message);
    }

    #[TestDox('Should calculate correct message length')]
    public function test_calculates_correct_length(): void
    {
        $testCases = [
            'Hello' => 5,
            'Привет' => 12, // UTF-8: по 2 байта на кириллический символ
            'Hello, мир!' => 14, // Смешанный текст
            'SMS' => 3,
            '' => 0, // Пустая строка для проверки логики
        ];

        foreach ($testCases as $text => $expectedLength) {
            if ($text === '') {
                // Пустое сообщение должно бросать исключение
                continue;
            }

            $message = new Message($text);
            $this->assertSame($expectedLength, $message->length(), "Failed for text: '$text'");
        }
    }

    #[TestDox('Should handle various text formats correctly: \$input')]
    #[DataProvider('messageTextProvider')]
    public function test_handles_various_text_formats(string $input, string $expected, int $expectedLength): void
    {
        $message = new Message($input);

        $this->assertSame($expected, $message->value);
        $this->assertSame($expectedLength, $message->length());
        $this->assertSame($expected, (string) $message);
    }

    public static function messageTextProvider(): array
    {
        return [
            'Simple text' => ['Hello', 'Hello', 5],
            'Text with spaces' => ['Hello world', 'Hello world', 11],
            'Text with numbers' => ['Order #12345', 'Order #12345', 12],
            'Cyrillic text' => ['Привет', 'Привет', 12],
            'Mixed languages' => ['Hello Мир', 'Hello Мир', 12],
            'Special characters' => ['Hello@world.com', 'Hello@world.com', 15],
            'Text with newlines' => ["Hello\nWorld", "Hello\nWorld", 11],
            'Text with tabs' => ["Hello\tWorld", "Hello\tWorld", 11],
            'Emoji text' => ['Hello 👋', 'Hello 👋', 10], // UTF-8 emoji
            'Long message' => [str_repeat('A', 160), str_repeat('A', 160), 160],
        ];
    }

    #[TestDox('Should throw exception for empty message')]
    public function test_throws_exception_for_empty_message(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Message cannot be empty'
        );

        new Message('');
    }

    #[TestDox('Should throw exception for whitespace-only message')]
    public function test_throws_exception_for_whitespace_only_message(): void
    {
        $this->expectExceptionWithMessage(
            InvalidArgument::class,
            'Message cannot be empty'
        );

        new Message('   ');
    }

    #[TestDox('Should handle edge cases with whitespace')]
    public function test_handles_whitespace_edge_cases(): void
    {
        // Сообщение с пробелами в начале и конце должно сохраняться как есть
        $message = new Message('  Hello World  ');

        $this->assertSame('  Hello World  ', $message->value);
        $this->assertSame(15, $message->length()); // 2 + 11 + 2 = 15
    }

    #[TestDox('Should preserve exact message content')]
    public function test_preserves_exact_content(): void
    {
        // Сообщение должно сохраняться в точности как передано
        $testCases = [
            "Hello\nWorld",      // С переносом строки
            "Hello\tWorld",      // С табуляцией
            "Hello  World",      // С двойными пробелами
            " Leading space",    // С пробелом в начале
            "Trailing space ",   // С пробелом в конце
            "Special chars: !@#$%^&*()", // Спецсимволы
        ];

        foreach ($testCases as $originalText) {
            $message = new Message($originalText);

            $this->assertSame($originalText, $message->value);
            $this->assertSame($originalText, (string) $message);
        }
    }

    #[TestDox('Should handle UTF-8 encoding correctly')]
    public function test_handles_utf8_encoding(): void
    {
        $testCases = [
            'Русский текст' => 25,        // 12 символов × 2 байта + 1 байт пробел
            '中文测试' => 12,                // 4 символа × 3 байта
            'Español ñáéíóú' => 21,       // Латиница с диакритикой
            '🚀 Emoji test' => 15,        // Emoji + текст
            '👨‍💻 Complex emoji' => 25,    // Составной emoji
        ];

        foreach ($testCases as $text => $expectedByteLength) {
            $message = new Message($text);

            $this->assertSame($text, $message->value);
            $this->assertSame($expectedByteLength, $message->length());
        }
    }

    #[TestDox('Should work with very long messages')]
    public function test_handles_long_messages(): void
    {
        // SMS обычно ограничены 160 символами, но проверим большие сообщения
        $longMessage = str_repeat('Long message text. ', 50); // ~950 символов

        $message = new Message($longMessage);

        $this->assertSame($longMessage, $message->value);
        $this->assertSame(strlen($longMessage), $message->length());
    }

    #[TestDox('Should handle single character messages')]
    public function test_handles_single_character(): void
    {
        $testCases = [
            'A' => 1,
            'я' => 2,  // Кириллица
            '中' => 3,  // Китайский символ
            '🚀' => 4, // Emoji
        ];

        foreach ($testCases as $char => $expectedLength) {
            $message = new Message($char);

            $this->assertSame($char, $message->value);
            $this->assertSame($expectedLength, $message->length());
        }
    }
}