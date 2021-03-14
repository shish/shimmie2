<?php declare(strict_types=1);
abstract class SCORE
{
    const AIPK      = "SCORE_AIPK";
    const INET      = "SCORE_INET";
}

abstract class DBEngine
{
    public ?string $name = null;

    public function init(PDO $db)
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

    abstract public function set_timeout(PDO $db, int $time);

    abstract public function get_version(PDO $db): string;

    abstract public function notify(PDO $db, string $channel, ?string $data=null): void;
}

class MySQL extends DBEngine
{
    public ?string $name = DatabaseDriver::MYSQL;

    public function init(PDO $db)
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

    public function set_timeout(PDO $db, int $time): void
    {
        // These only apply to read-only queries, which appears to be the best we can to mysql-wise
        // $db->exec("SET SESSION MAX_EXECUTION_TIME=".$time.";");
    }

    public function notify(PDO $db, string $channel, ?string $data=null): void
    {
    }

    public function get_version(PDO $db): string
    {
        return $db->query('select version()')->fetch()[0];
    }
}

class PostgreSQL extends DBEngine
{
    public ?string $name = DatabaseDriver::PGSQL;

    public function init(PDO $db)
    {
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $db->exec("SET application_name TO 'shimmie [{$_SERVER['REMOTE_ADDR']}]';");
        } else {
            $db->exec("SET application_name TO 'shimmie [local]';");
        }
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

    public function set_timeout(PDO $db, int $time): void
    {
        $db->exec("SET statement_timeout TO ".$time.";");
    }

    public function notify(PDO $db, string $channel, ?string $data=null): void
    {
        if ($data) {
            $db->exec("NOTIFY $channel, '$data';");
        } else {
            $db->exec("NOTIFY $channel;");
        }
    }

    public function get_version(PDO $db): string
    {
        return $db->query('select version()')->fetch()[0];
    }
}

// shimmie functions for export to sqlite
function _unix_timestamp($date): int
{
    return strtotime($date);
}
function _now(): string
{
    return date("Y-m-d H:i:s");
}
function _floor($a): float
{
    return floor($a);
}
function _log($a, $b=null): float
{
    if (is_null($b)) {
        return log($a);
    } else {
        return log($a, $b);
    }
}
function _isnull($a): bool
{
    return is_null($a);
}
function _md5($a): string
{
    return md5($a);
}
function _concat($a, $b): string
{
    return $a . $b;
}
function _lower($a): string
{
    return strtolower($a);
}
function _rand(): int
{
    return rand();
}
function _ln($n): float
{
    return log($n);
}

class SQLite extends DBEngine
{
    public ?string $name = DatabaseDriver::SQLITE;

    public function init(PDO $db)
    {
        ini_set('sqlite.assoc_case', '0');
        $db->exec("PRAGMA foreign_keys = ON;");
        $db->sqliteCreateFunction('UNIX_TIMESTAMP', '_unix_timestamp', 1);
        $db->sqliteCreateFunction('now', '_now', 0);
        $db->sqliteCreateFunction('floor', '_floor', 1);
        $db->sqliteCreateFunction('log', '_log');
        $db->sqliteCreateFunction('isnull', '_isnull', 1);
        $db->sqliteCreateFunction('md5', '_md5', 1);
        $db->sqliteCreateFunction('concat', '_concat', 2);
        $db->sqliteCreateFunction('lower', '_lower', 1);
        $db->sqliteCreateFunction('rand', '_rand', 0);
        $db->sqliteCreateFunction('ln', '_ln', 1);
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

    public function set_timeout(PDO $db, int $time): void
    {
        // There doesn't seem to be such a thing for SQLite, so it does nothing
    }

    public function notify(PDO $db, string $channel, ?string $data=null): void
    {
    }

    public function get_version(PDO $db): string
    {
        return $db->query('select sqlite_version()')->fetch()[0];
    }
}
