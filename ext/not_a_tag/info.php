<?php declare(strict_types=1);

class NotATagInfo extends ExtensionInfo
{
    public const KEY = "not_a_tag";

    public $key = self::KEY;
    public $name = "Not A Tag";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Redirect users to the rules if they use bad tags";
}
