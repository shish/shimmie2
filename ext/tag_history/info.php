<?php

/*
 * Name: Tag History
 * Author: Bzchan <bzchan@animemahou.com>, modified by jgen <jgen.tech@gmail.com>
 * Description: Keep a record of tag changes, and allows you to revert changes.
 */

class Tag_HistoryInfo extends ExtensionInfo
{
    public const KEY = "tag_history";

    public $key = self::KEY;
    public $name = "Tag History";
    public $authors = ["Bzchan"=>"bzchan@animemahou.com","jgen"=>"jgen.tech@gmail.com"];
    public $description = "Keep a record of tag changes, and allows you to revert changes.";
}
