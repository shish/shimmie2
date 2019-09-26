<?php

/*
 * Name: Source History
 * Author: Shish, copied from Source History
 * Description: Keep a record of source changes, and allows you to revert changes.
 */

class Source_HistoryInfo extends ExtensionInfo
{
    public const KEY = "source_history";

    public $key = self::KEY;
    public $name = "Source History";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Keep a record of source changes, and allows you to revert changes.";
}
