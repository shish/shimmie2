<?php declare(strict_types=1);

class SourceHistoryInfo extends ExtensionInfo
{
    public const KEY = "source_history";

    public $key = self::KEY;
    public $name = "Source History";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Keep a record of source changes, and allows you to revert changes.";
}
