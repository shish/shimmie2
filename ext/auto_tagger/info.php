<?php declare(strict_types=1);

class AutoTaggerInfo extends ExtensionInfo
{
    public const KEY = "auto_tagger";

    public $key = self::KEY;
    public $name = "Auto-Tagger";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides several automatic tagging functions";
}
