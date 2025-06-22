<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\DTO\SMS;

interface SmsSenderInterface
{
    public function send(SMS $sms): void;
}