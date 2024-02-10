<?php

declare(strict_types=1);

namespace Shimmie2;

class LogLogstashInfo extends ExtensionInfo
{
    public const KEY = "log_logstash";

    public string $key = self::KEY;
    public string $name = "Logging (Logstash)";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Send log events to a network port.";
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
