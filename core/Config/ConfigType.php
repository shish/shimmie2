<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * @phpstan-import-type ConfigValue from Config
 */
enum ConfigType
{
    case BOOL;
    case INT;
    case STRING;
    case ARRAY;

    /**
     * @param ConfigValue $value
     */
    public function toString(mixed $value): string
    {
        return match ($this) {
            self::BOOL => $value ? "Y" : "N",
            self::INT => (string)$value,  // @phpstan-ignore-line
            self::STRING => $value,
            self::ARRAY => implode(",", $value),  // @phpstan-ignore-line
        };
    }

    /**
     * @return ConfigValue
     */
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
