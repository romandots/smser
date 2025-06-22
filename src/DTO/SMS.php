<?php

namespace Romandots\Smser\DTO;

use Romandots\Smser\Value\Message;
use Romandots\Smser\Value\PhoneNumber;
use Romandots\Smser\Value\Provider;

readonly class SMS
{
    public function __construct(
        public PhoneNumber $phoneNumber,
        public Message $message,
        public Provider $provider,
    ) {
    }
}