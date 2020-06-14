<?php declare(strict_types=1);

class MimeSystemInfo extends ExtensionInfo
{
    public const KEY = "mime";

    public $key = self::KEY;
    public $name = "MIME";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides system mime-related functionality";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
