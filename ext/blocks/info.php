<?php

/*
 * Name: Generic Blocks
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Add HTML to some space (News, Ads, etc)
 */

class BlocksInfo extends ExtensionInfo
{
    public const KEY = "blocks";

    public $key = self::KEY;
    public $name = "Generic Blocks";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Add HTML to some space (News, Ads, etc)";
}
