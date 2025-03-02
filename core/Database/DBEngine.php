<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;

abstract class DBEngine
{
    public DatabaseDriverID $id;

    public function init(PDO $db): void
    {
    }

    public function scoreql_to_sql(string $data): string
    {
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        return 'CREATE TABLE '.$name.' ('.$data.')';
    }

    abstract public function set_timeout(PDO $db, ?int $time): void;

    abstract public function get_version(PDO $db): string;

    abstract public function notify(PDO $db, string $channel, ?string $data = null): void;
}
