<?php

namespace Romandots\Smser\DTO;

use Romandots\Smser\Services\ProviderDeterminationService;
use Romandots\Smser\Value\Message;
use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Value\Provider;

readonly class SMS
{
    private function __construct(
        public PhoneNumber $phoneNumber,
        public Message $message,
        public Provider $provider,
    ) {
    }

    public static function make(
        string $phone,
        string $msg,
    ): SMS {
        $phoneNumber = new PhoneNumber($phone);
        $provider = ProviderDeterminationService::determineProvider($phoneNumber);
        return new SMS($phoneNumber, new Message($msg), $provider);
    }
}