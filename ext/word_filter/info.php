<?php

/*
 * Name: Word Filter
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Simple search and replace
 */

class WordFilterInfo extends ExtensionInfo
{
    public const KEY = "word_filter";

    public $key = self::KEY;
    public $name = "Word Filter";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Simple search and replace";
}
