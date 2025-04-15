<?php

declare(strict_types=1);

namespace Shimmie2;

enum PageMode
{
    case REDIRECT;
    case DATA;
    case PAGE;
    case FILE;
    case MANUAL;
    case ERROR;
}
