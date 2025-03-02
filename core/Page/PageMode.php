<?php

declare(strict_types=1);

namespace Shimmie2;

enum PageMode: string
{
    case REDIRECT = 'redirect';
    case DATA = 'data';
    case PAGE = 'page';
    case FILE = 'file';
    case MANUAL = 'manual';
}
