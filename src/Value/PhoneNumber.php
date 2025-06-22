<?php

namespace Romandots\Smser\Value;

use Romandots\Smser\Exceptions\InvalidArgument;

readonly class PhoneNumber
{
    public const string COUNTRY_CODE = '7';
    public string $value;

    public function __construct(string $value)
    {
        $value = preg_replace('/\D/', '', $value);

        if (empty($value)) {
            throw new InvalidArgument("Phone number cannot be empty");
        }

        if (!is_numeric($value)) {
            throw new InvalidArgument("Phone number must be numeric");
        }

        if (strlen($value) === 10 && !str_starts_with($value, self::COUNTRY_CODE)) {
            $value = self::COUNTRY_CODE . $value;
        }

        if (strlen($value) !== 11) {
            throw new InvalidArgument("Phone number must be 11 digits");
        }

        if (str_starts_with($value, '8')) {
            $value = self::COUNTRY_CODE . substr($value, 1);
        }

        if (!str_starts_with($value, self::COUNTRY_CODE)) {
            throw new InvalidArgument("Phone number must start with " . self::COUNTRY_CODE);
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}