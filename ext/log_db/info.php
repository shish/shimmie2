<?php declare(strict_types=1);

class LogDatabaseInfo extends ExtensionInfo
{
    public const KEY = "log_db";

    public $key = self::KEY;
    public $name = "Logging (Database)";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Keep a record of SCore events (in the database).";
    public $visibility = self::VISIBLE_ADMIN;
}
