<?php declare(strict_types=1);

class PMTriggerInfo extends ExtensionInfo
{
    public const KEY = "pm_triggers";

    public string $key = self::KEY;
    public string $name = "PM triggers";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Send PMs in response to certain events (eg post deletion)";
    public bool $beta = true;
}
