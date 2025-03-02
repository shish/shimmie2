<?php

declare(strict_types=1);

namespace Shimmie2;

enum ConfigType
{
    case BOOL;
    case INT;
    case STRING;
    case ARRAY;
}
