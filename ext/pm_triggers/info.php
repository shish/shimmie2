<?php declare(strict_types=1);

class PMTriggerInfo extends ExtensionInfo
{
    public const KEY = "pm_triggers";

    public $key = self::KEY;
    public $name = "PM triggers";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Send PMs in response to certain events (eg image deletion)";
    public $beta = true;
}
