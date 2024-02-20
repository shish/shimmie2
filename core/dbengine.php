<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;

abstract class SCORE
{
    public const AIPK      = "SCORE_AIPK";
    public const INET      = "SCORE_INET";
}

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

class MySQL extends DBEngine
{
    public DatabaseDriverID $id = DatabaseDriverID::MYSQL;

    public function init(PDO $db): void
    {
        $db->exec("SET NAMES utf8;");
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace(SCORE::AIPK, "INTEGER PRIMARY KEY auto_increment", $data);
        $data = str_replace(SCORE::INET, "VARCHAR(45)", $data);
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

class PostgreSQL extends DBEngine
{
    public DatabaseDriverID $id = DatabaseDriverID::PGSQL;

    public function init(PDO $db): void
    {
        $addr = array_key_exists('REMOTE_ADDR', $_SERVER) ? get_real_ip() : 'local';
        $db->exec("SET application_name TO 'shimmie [$addr]';");
        if (defined("DATABASE_TIMEOUT")) {
            $this->set_timeout($db, DATABASE_TIMEOUT);
        }
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace(SCORE::AIPK, "INTEGER NOT NULL PRIMARY KEY GENERATED ALWAYS AS IDENTITY", $data);
        $data = str_replace(SCORE::INET, "INET", $data);
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

// shimmie functions for export to sqlite
function _unix_timestamp(string $date): int
{
    return \Safe\strtotime($date);
}
function _now(): string
{
    return date("Y-m-d H:i:s");
}
function _floor(float|int $a): float
{
    return floor($a);
}
function _log(float $a, ?float $b = null): float
{
    if (is_null($b)) {
        return log($a);
    } else {
        return log($b, $a);
    }
}
function _isnull(mixed $a): bool
{
    return is_null($a);
}
function _md5(string $a): string
{
    return md5($a);
}
function _concat(string $a, string $b): string
{
    return $a . $b;
}
function _lower(string $a): string
{
    return strtolower($a);
}
function _rand(): int
{
    return rand();
}
function _ln(float $n): float
{
    return log($n);
}

class SQLite extends DBEngine
{
    public DatabaseDriverID $id = DatabaseDriverID::SQLITE;

    public function init(PDO $db): void
    {
        ini_set('sqlite.assoc_case', '0');
        $db->exec("PRAGMA foreign_keys = ON;");
        $db->sqliteCreateFunction('UNIX_TIMESTAMP', 'Shimmie2\_unix_timestamp', 1);
        $db->sqliteCreateFunction('now', 'Shimmie2\_now', 0);
        $db->sqliteCreateFunction('floor', 'Shimmie2\_floor', 1);
        $db->sqliteCreateFunction('log', 'Shimmie2\_log');
        $db->sqliteCreateFunction('isnull', 'Shimmie2\_isnull', 1);
        $db->sqliteCreateFunction('md5', 'Shimmie2\_md5', 1);
        $db->sqliteCreateFunction('concat', 'Shimmie2\_concat', 2);
        $db->sqliteCreateFunction('lower', 'Shimmie2\_lower', 1);
        $db->sqliteCreateFunction('rand', 'Shimmie2\_rand', 0);
        $db->sqliteCreateFunction('ln', 'Shimmie2\_ln', 1);
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace(SCORE::AIPK, "INTEGER PRIMARY KEY", $data);
        $data = str_replace(SCORE::INET, "VARCHAR(45)", $data);
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        $data = $this->scoreql_to_sql($data);
        $cols = [];
        $extras = "";
        foreach (explode(",", $data) as $bit) {
            $matches = [];
            if (preg_match("/(UNIQUE)? ?INDEX\s*\((.*)\)/", $bit, $matches)) {
                $uni = $matches[1];
                $col = $matches[2];
                $extras .= "CREATE $uni INDEX {$name}_{$col} ON {$name}({$col});";
            } else {
                $cols[] = $bit;
            }
        }
        $cols_redone = implode(", ", $cols);
        return "CREATE TABLE $name ($cols_redone); $extras";
    }

    public function set_timeout(PDO $db, ?int $time): void
    {
        // There doesn't seem to be such a thing for SQLite, so it does nothing
    }

    public function notify(PDO $db, string $channel, ?string $data = null): void
    {
    }

    public function get_version(PDO $db): string
    {
        return $db->execute('select sqlite_version()')->fetch()[0];
    }
}
