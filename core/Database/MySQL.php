<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;

class MySQL extends DBEngine
{
    public DatabaseDriverID $id = DatabaseDriverID::MYSQL;

    public function init(PDO $db): void
    {
        $db->exec("SET NAMES utf8;");
        $db->exec("SET SESSION sql_mode='ANSI,TRADITIONAL';");
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY auto_increment", $data);
        $data = str_replace("SCORE_INET", "VARCHAR(45)", $data);
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        $data = $this->scoreql_to_sql($data);
        $ctes = "ENGINE=InnoDB DEFAULT CHARSET='utf8'";
        return 'CREATE TABLE '.$name.' ('.$data.') '.$ctes;
    }

    public function set_timeout(PDO $db, ?int $time): void
    {
        // These only apply to read-only queries, which appears to be the best we can to mysql-wise
        // $db->exec("SET SESSION MAX_EXECUTION_TIME=".$time.";");
    }

    public function notify(PDO $db, string $channel, ?string $data = null): void
    {
    }

    public function get_version(PDO $db): string
    {
        return $db->execute('select version()')->fetch()[0];
    }
}
