<?php

declare(strict_types=1);

namespace Shimmie2;

enum LogLevel: int
{
    case NOT_SET = 0;
    case DEBUG = 10;
    case INFO = 20;
    case WARNING = 30;
    case ERROR = 40;
    case CRITICAL = 50;

    /**
     * @return array<string, int>
     */
    public static function names_to_levels(): array
    {
        $ret = [];
        foreach (LogLevel::cases() as $case) {
            $ret[$case->name] = $case->value;
        }
        return $ret;
    }
}
