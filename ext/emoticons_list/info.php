<?php declare(strict_types=1);

class EmoticonListInfo extends ExtensionInfo
{
    public const KEY = "emoticons_list";

    public $key = self::KEY;
    public $name = "Emoticon List";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Lists available graphical smilies";

    public $visibility = self::VISIBLE_HIDDEN;
}
