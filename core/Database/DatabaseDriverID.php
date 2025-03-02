<?php

declare(strict_types=1);

namespace Shimmie2;

enum DatabaseDriverID: string
{
    case MYSQL = "mysql";
    case PGSQL = "pgsql";
    case SQLITE = "sqlite";
}
