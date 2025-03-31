<?php

declare(strict_types=1);

namespace Shimmie2;

enum UserClassSource
{
    case UNKNOWN;
    case DEFAULT;
    case FILE;
    case DATABASE;
}
