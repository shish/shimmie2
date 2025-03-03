<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;

class SQLite extends DBEngine
{
    public DatabaseDriverID $id = DatabaseDriverID::SQLITE;

    public function init(PDO $db): void
    {
        ini_set('sqlite.assoc_case', '0');
        $db->exec("PRAGMA foreign_keys = ON;");
        $db->sqliteCreateFunction('now', fn (): string => date("Y-m-d H:i:s"), 0);
        $db->sqliteCreateFunction('md5', fn (string $a): string => md5($a), 1);
        $db->sqliteCreateFunction('lower', fn (string $a): string => mb_strtolower($a), 1);
        $db->sqliteCreateFunction('rand', fn (): int => rand(), 0);
    }

    public function scoreql_to_sql(string $data): string
    {
        $data = str_replace("SCORE_AIPK", "INTEGER PRIMARY KEY", $data);
        $data = str_replace("SCORE_INET", "VARCHAR(45)", $data);
        return $data;
    }

    public function create_table_sql(string $name, string $data): string
    {
        $data = $this->scoreql_to_sql($data);
        $cols = [];
        $extras = "";
        foreach (explode(",", $data) as $bit) {
            $matches = [];
            if (\Safe\preg_match("/(UNIQUE)? ?INDEX\s*\((.*)\)/", $bit, $matches)) {
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
