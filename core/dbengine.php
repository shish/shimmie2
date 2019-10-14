<?php
abstract class SCORE
{
    const AIPK      = "SCORE_AIPK";
    const INET      = "SCORE_INET";
    const BOOL_Y    = "SCORE_BOOL_Y";
    const BOOL_N    = "SCORE_BOOL_N";
    const BOOL      = "SCORE_BOOL";
    const DATETIME  = "SCORE_DATETIME";
    const NOW       = "SCORE_NOW";
    const STRNORM   = "SCORE_STRNORM";
    const ILIKE     = "SCORE_ILIKE";
}

abstract class DBEngine
{
    /** @var null|string */
    public $name = null;

    public $BOOL_Y = null;
    public $BOOL_N = null;

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

    public abstract function set_timeout(PDO $db, int $time);
}

class MySQL extends DBEngine
{
    /** @var string */
    public $name = DatabaseDriver::MYSQL;

    public $BOOL_Y = 'Y';
    public $BOOL_N = 'N';

    public function init(PDO $db)
    {
        $db->exec("SET NAMES utf8;");
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace(SCORE::AIPK, "INTEGER PRIMARY KEY auto_increment", $data);
        $data = str_replace(SCORE::INET, "VARCHAR(45)", $data);
        $data = str_replace(SCORE::BOOL_Y, "'$this->BOOL_Y'", $data);
        $data = str_replace(SCORE::BOOL_N, "'$this->BOOL_N'", $data);
        $data = str_replace(SCORE::BOOL, "ENUM('Y', 'N')", $data);
        $data = str_replace(SCORE::DATETIME, "DATETIME", $data);
        $data = str_replace(SCORE::NOW, "\"1970-01-01\"", $data);
        $data = str_replace(SCORE::STRNORM, "", $data);
        $data = str_replace(SCORE::ILIKE, "LIKE", $data);
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
        $db->exec("SET SESSION MAX_EXECUTION_TIME=".$time.";");
    }

}

class PostgreSQL extends DBEngine
{


    /** @var string */
    public $name = DatabaseDriver::PGSQL;

    public $BOOL_Y = 'true';
    public $BOOL_N = 'false';

    public function init(PDO $db)
    {
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $db->exec("SET application_name TO 'shimmie [{$_SERVER['REMOTE_ADDR']}]';");
        } else {
            $db->exec("SET application_name TO 'shimmie [local]';");
        }
        $this->set_timeout($db, DATABASE_TIMEOUT);
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace(SCORE::AIPK, "SERIAL PRIMARY KEY", $data);
        $data = str_replace(SCORE::INET, "INET", $data);
        $data = str_replace(SCORE::BOOL_Y, $this->BOOL_Y, $data);
        $data = str_replace(SCORE::BOOL_N, $this->BOOL_N, $data);
        $data = str_replace(SCORE::BOOL, "BOOL", $data);
        $data = str_replace(SCORE::DATETIME, "TIMESTAMP", $data);
        $data = str_replace(SCORE::NOW, "current_timestamp", $data);
        $data = str_replace(SCORE::STRNORM, "lower", $data);
        $data = str_replace(SCORE::ILIKE, "ILIKE", $data);
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

    public $BOOL_Y = 'Y';
    public $BOOL_N = 'N';


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
        $data = str_replace(SCORE::AIPK, "INTEGER PRIMARY KEY", $data);
        $data = str_replace(SCORE::INET, "VARCHAR(45)", $data);
        $data = str_replace(SCORE::BOOL_Y, "'$this->BOOL_Y'", $data);
        $data = str_replace(SCORE::BOOL_N, "'$this->BOOL_N'", $data);
        $data = str_replace(SCORE::BOOL, "CHAR(1)", $data);
        $data = str_replace(SCORE::NOW, "\"1970-01-01\"", $data);
        $data = str_replace(SCORE::STRNORM, "lower", $data);
        $data = str_replace(SCORE::ILIKE, "LIKE", $data);
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
}
