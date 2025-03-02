<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;

class PostgreSQL extends DBEngine
{
    public DatabaseDriverID $id = DatabaseDriverID::PGSQL;

    public function init(PDO $db): void
    {
        $addr = array_key_exists('REMOTE_ADDR', $_SERVER) ? Network::get_real_ip() : 'local';
        $db->exec("SET application_name TO 'shimmie [$addr]';");
        if (defined("DATABASE_TIMEOUT")) {
            $this->set_timeout($db, DATABASE_TIMEOUT);
        }
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace("SCORE_AIPK", "INTEGER NOT NULL PRIMARY KEY GENERATED ALWAYS AS IDENTITY", $data);
        $data = str_replace("SCORE_INET", "INET", $data);
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        $data = $this->scoreql_to_sql($data);
        return "CREATE TABLE $name ($data)";
    }

    public function set_timeout(PDO $db, ?int $time): void
    {
        if (is_null($time)) {
            $time = 0;
        }
        $db->exec("SET statement_timeout TO ".$time.";");
    }

    public function notify(PDO $db, string $channel, ?string $data = null): void
    {
        if ($data) {
            $db->exec("NOTIFY $channel, '$data';");
        } else {
            $db->exec("NOTIFY $channel;");
        }
    }

    public function get_version(PDO $db): string
    {
        return $db->execute('select version()')->fetch()[0];
    }
}
