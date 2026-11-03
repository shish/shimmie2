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
     * Returns string:string instead of string:int because it is
     * used for HTML <select> options
     *
     * @return array<string, string>
     */
    public static function names_to_levels(): array
    {
        $ret = [];
        foreach (LogLevel::cases() as $case) {
            $ret[$case->name] = (string)$case->value;
        }
        return $ret;
    }
}
