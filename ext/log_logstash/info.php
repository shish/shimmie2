<?php declare(strict_types=1);

class LogLogstashInfo extends ExtensionInfo
{
    public const KEY = "log_logstash";

    public string $key = self::KEY;
    public string $name = "Logging (Logstash)";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Send log events to a network port.";
    public string $visibility = self::VISIBLE_ADMIN;
}
