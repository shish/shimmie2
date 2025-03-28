<?php

declare(strict_types=1);

namespace Shimmie2;

enum ConfigType
{
    case BOOL;
    case INT;
    case STRING;
    case ARRAY;

    public function toString(mixed $value): string
    {
        return match ($this) {
            self::BOOL => $value ? "Y" : "N",
            self::INT => (string)$value,
            self::STRING => $value,
            self::ARRAY => implode(",", $value),
        };
    }

    public function fromString(string $value): mixed
    {
        return match ($this) {
            self::BOOL => bool_escape($value),
            self::INT => (int)$value,
            self::STRING => $value,
            self::ARRAY => explode(",", $value),
        };
    }
}
