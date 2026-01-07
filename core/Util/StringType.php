<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Types of strings that can be checked for spam / curse words / etc.
 */
enum StringType: string
{
    case TAG = "tag";
    case URL = "url";
    case TEXT = "text";
}
