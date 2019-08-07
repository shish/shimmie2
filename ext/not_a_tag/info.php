<?php

/*
 * Name: Not A Tag
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Redirect users to the rules if they use bad tags
 */
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
