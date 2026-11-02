<?php

declare(strict_types=1);

namespace Shimmie2;

enum ConfigInput
{
    case CHECKBOX;
    case NUMBER;
    case BYTES;
    case TEXT;
    case PASSWORD;
    case TEXTAREA;
    case COLOR;
    case MULTICHOICE;
}
