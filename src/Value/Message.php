<?php

namespace Romandots\Smser\Value;

use Romandots\Smser\Exceptions\InvalidArgument;

readonly class Message
{
    public string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgument("Message cannot be empty");
        }

        $this->value = $value;
    }

    public function length(): int
    {
        return strlen($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}