<?php declare(strict_types=1);

class TagHistoryInfo extends ExtensionInfo
{
    public const KEY = "tag_history";

    public $key = self::KEY;
    public $name = "Tag History";
    public $authors = ["Bzchan"=>"bzchan@animemahou.com","jgen"=>"jgen.tech@gmail.com"];
    public $description = "Keep a record of tag changes, and allows you to revert changes.";
}
