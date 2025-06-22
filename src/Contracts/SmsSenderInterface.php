<?php

namespace Romandots\Smser\Contracts;

use Romandots\Smser\DTO\SMS;
use Romandots\Smser\Exceptions\InsufficientBalance;
use Romandots\Smser\Exceptions\ServiceUnavailable;

/**
 * @throws ServiceUnavailable
 * @throws InsufficientBalance
 */
interface SmsSenderInterface
{
    /**
     * @param SMS $sms
     * @return float Sent message cost
     */
    public function send(SMS $sms): float;
}