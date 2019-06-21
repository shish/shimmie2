<?php
class DBEngine
{
    /** @var null|string */
    public $name = null;

    public function init(PDO $db)
    {
    }

    public function scoreql_to_sql(string $scoreql): string
    {
        return $scoreql;
    }

    public function create_table_sql(string $name, string $data): string
    {
        return 'CREATE TABLE '.$name.' ('.$data.')';
    }
}

class MySQL extends DBEngine
{
    /** @var string */
    public $name = DatabaseDriver::MYSQL;

    public function init(PDO $db)
    {
        $db->exec("SET NAMES utf8;");
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY auto_increment", $data);
        $data = str_replace("SCORE_INET", "VARCHAR(45)", $data);
        $data = str_replace("SCORE_BOOL_Y", "'Y'", $data);
        $data = str_replace("SCORE_BOOL_N", "'N'", $data);
        $data = str_replace("SCORE_BOOL", "ENUM('Y', 'N')", $data);
        $data = str_replace("SCORE_DATETIME", "DATETIME", $data);
        $data = str_replace("SCORE_NOW", "\"1970-01-01\"", $data);
        $data = str_replace("SCORE_STRNORM", "", $data);
        $data = str_replace("SCORE_ILIKE", "LIKE", $data);
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        $data = $this->scoreql_to_sql($data);
        $ctes = "ENGINE=InnoDB DEFAULT CHARSET='utf8'";
        return 'CREATE TABLE '.$name.' ('.$data.') '.$ctes;
    }
}

class PostgreSQL extends DBEngine
{
    /** @var string */
    public $name = DatabaseDriver::PGSQL;

    public function init(PDO $db)
    {
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $db->exec("SET application_name TO 'shimmie [{$_SERVER['REMOTE_ADDR']}]';");
        } else {
            $db->exec("SET application_name TO 'shimmie [local]';");
        }
        $db->exec("SET statement_timeout TO 10000;");
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace("SCORE_AIPK", "SERIAL PRIMARY KEY", $data);
        $data = str_replace("SCORE_INET", "INET", $data);
        $data = str_replace("SCORE_BOOL_Y", "'t'", $data);
        $data = str_replace("SCORE_BOOL_N", "'f'", $data);
        $data = str_replace("SCORE_BOOL", "BOOL", $data);
        $data = str_replace("SCORE_DATETIME", "TIMESTAMP", $data);
        $data = str_replace("SCORE_NOW", "current_timestamp", $data);
        $data = str_replace("SCORE_STRNORM", "lower", $data);
        $data = str_replace("SCORE_ILIKE", "ILIKE", $data);
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        $data = $this->scoreql_to_sql($data);
        return "CREATE TABLE $name ($data)";
    }
}

// shimmie functions for export to sqlite
function _unix_timestamp($date)
{
    return strtotime($date);
}
function _now()
{
    return date("Y-m-d h:i:s");
}
function _floor($a)
{
    return floor($a);
}
function _log($a, $b=null)
{
    if (is_null($b)) {
        return log($a);
    } else {
        return log($a, $b);
    }
}
function _isnull($a)
{
    return is_null($a);
}
function _md5($a)
{
    return md5($a);
}
function _concat($a, $b)
{
    return $a . $b;
}
function _lower($a)
{
    return strtolower($a);
}
function _rand()
{
    return rand();
}
function _ln($n)
{
    return log($n);
}

class SQLite extends DBEngine
{
    /** @var string  */
    public $name = DatabaseDriver::SQLITE;

    public function init(PDO $db)
    {
        ini_set('sqlite.assoc_case', 0);
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
        $data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY", $data);
        $data = str_replace("SCORE_INET", "VARCHAR(45)", $data);
        $data = str_replace("SCORE_BOOL_Y", "'Y'", $data);
        $data = str_replace("SCORE_BOOL_N", "'N'", $data);
        $data = str_replace("SCORE_BOOL", "CHAR(1)", $data);
        $data = str_replace("SCORE_NOW", "\"1970-01-01\"", $data);
        $data = str_replace("SCORE_STRNORM", "lower", $data);
        $data = str_replace("SCORE_ILIKE", "LIKE", $data);
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
}
