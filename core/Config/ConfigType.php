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

    /**
     * @param string|array<string>|null $value
     * @return ConfigValue|null
     */
    public function fromPost(string|array|null $value): mixed
    {
        return match ($this) {
            self::BOOL => (is_null($value) || is_array($value)) ? false : bool_escape($value),
            self::INT => (is_null($value) || is_array($value) || $value === "") ? null : parse_shorthand_int($value),
            self::STRING => (is_null($value) || is_array($value) || $value === "") ? null : $value,
            self::ARRAY => is_array($value) ? $value : [],
        };
    }
}
