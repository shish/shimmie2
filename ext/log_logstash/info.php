<?php declare(strict_types=1);

class LogLogstashInfo extends ExtensionInfo
{
    public const KEY = "log_logstash";

    public $key = self::KEY;
    public $name = "Logging (Logstash)";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Send log events to a network port.";
    public $visibility = self::VISIBLE_ADMIN;
}
