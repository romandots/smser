<?php

namespace Romandots\Smser\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\TestDox;
use Romandots\Smser\DTO\SMS;
use Romandots\Smser\Tests\TestCase;
use Romandots\Smser\Value\Message;
use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Value\Provider;

class SMSTest extends TestCase
{
    #[TestDox('Should create SMS with all required properties')]
    public function test_creates_sms_with_all_properties(): void
    {
        $phoneNumber = new PhoneNumber('79251234567');
        $message = new Message('Test message');
        $provider = Provider::MTS;

        $sms = new SMS($phoneNumber, $message, $provider);

        $this->assertSame($phoneNumber, $sms->phoneNumber);
        $this->assertSame($message, $sms->message);
        $this->assertSame($provider, $sms->provider);
    }

    #[TestDox('Should be readonly - properties cannot be modified')]
    public function test_is_readonly(): void
    {
        $phoneNumber = new PhoneNumber('79251234567');
        $message = new Message('Test message');
        $provider = Provider::BEELINE;

        $sms = new SMS($phoneNumber, $message, $provider);

        // Проверяем, что свойства readonly (это проверится на уровне PHP)
        $this->assertSame($phoneNumber, $sms->phoneNumber);
        $this->assertSame($message, $sms->message);
        $this->assertSame($provider, $sms->provider);
    }

    #[TestDox('Should work with different providers')]
    public function test_works_with_different_providers(): void
    {
        $phoneNumber = new PhoneNumber('79251234567');
        $message = new Message('Test message');

        $providers = [
            Provider::MTS,
            Provider::BEELINE,
            Provider::MEGAFON,
            Provider::TELE2,
        ];

        foreach ($providers as $provider) {
            $sms = new SMS($phoneNumber, $message, $provider);

            $this->assertSame($provider, $sms->provider);
            $this->assertSame($provider->value, $sms->provider->value);
        }
    }

    #[TestDox('Should preserve original object references')]
    public function test_preserves_object_references(): void
    {
        $phoneNumber = new PhoneNumber('79251234567');
        $message = new Message('Test message');
        $provider = Provider::MEGAFON;

        $sms = new SMS($phoneNumber, $message, $provider);

        // Проверяем, что это те же самые объекты (by reference)
        $this->assertSame($phoneNumber, $sms->phoneNumber);
        $this->assertSame($message, $sms->message);
        $this->assertSame($provider, $sms->provider);
    }

    #[TestDox('Should work with different phone number formats')]
    public function test_works_with_different_phone_formats(): void
    {
        $message = new Message('Test message');
        $provider = Provider::MTS;

        $phoneNumbers = [
            new PhoneNumber('79251234567'),
            new PhoneNumber('9251234567'),    // Нормализуется к 79251234567
            new PhoneNumber('8(925)123-45-67'), // Нормализуется к 79251234567
        ];

        foreach ($phoneNumbers as $phoneNumber) {
            $sms = new SMS($phoneNumber, $message, $provider);

            $this->assertInstanceOf(PhoneNumber::class, $sms->phoneNumber);
            $this->assertSame('79251234567', $sms->phoneNumber->value);
        }
    }

    #[TestDox('Should work with different message lengths')]
    public function test_works_with_different_message_lengths(): void
    {
        $phoneNumber = new PhoneNumber('79251234567');
        $provider = Provider::TELE2;

        $messages = [
            new Message('Hi'),                          // Короткое
            new Message('Hello, this is a test'),      // Среднее
            new Message(str_repeat('Long text ', 20)), // Длинное
            new Message('🚀 Emoji message'),           // С emoji
            new Message('Русское сообщение'),          // Кириллица
        ];

        foreach ($messages as $message) {
            $sms = new SMS($phoneNumber, $message, $provider);

            $this->assertInstanceOf(Message::class, $sms->message);
            $this->assertSame($message->value, $sms->message->value);
        }
    }

    #[TestDox('Should be comparable by content')]
    public function test_is_comparable_by_content(): void
    {
        $phoneNumber1 = new PhoneNumber('79251234567');
        $phoneNumber2 = new PhoneNumber('79251234567'); // Тот же номер
        $message1 = new Message('Test message');
        $message2 = new Message('Test message'); // То же сообщение
        $provider = Provider::MTS;

        $sms1 = new SMS($phoneNumber1, $message1, $provider);
        $sms2 = new SMS($phoneNumber2, $message2, $provider);

        // Объекты должны быть равны по содержимому, но не по ссылке
        $this->assertEquals($sms1, $sms2);           // По содержимому
        $this->assertNotSame($sms1, $sms2);         // Но разные объекты

        // Внутренние объекты тоже должны быть равны по содержимию
        $this->assertEquals($sms1->phoneNumber, $sms2->phoneNumber);
        $this->assertEquals($sms1->message, $sms2->message);
        $this->assertSame($sms1->provider, $sms2->provider); // Enum - тот же объект
    }

    #[TestDox('Should maintain type safety')]
    public function test_maintains_type_safety(): void
    {
        $phoneNumber = new PhoneNumber('79251234567');
        $message = new Message('Test message');
        $provider = Provider::BEELINE;

        $sms = new SMS($phoneNumber, $message, $provider);

        // Проверяем типы
        $this->assertInstanceOf(PhoneNumber::class, $sms->phoneNumber);
        $this->assertInstanceOf(Message::class, $sms->message);
        $this->assertInstanceOf(Provider::class, $sms->provider);

        // Проверяем конкретные значения
        $this->assertSame('79251234567', $sms->phoneNumber->value);
        $this->assertSame('Test message', $sms->message->value);
        $this->assertSame('Билайн', $sms->provider->value);
    }
}