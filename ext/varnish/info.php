<?php

/*
* Name: Varnish Purger
* Author: Shish <webmaster@shishnet.org>
* License: GPLv2
* Visibility: admin
* Description: Sends PURGE requests when a /post/view is updated
*/

class VarnishPurgerInfo extends ExtensionInfo
{
    public const KEY = "varnish";

    public $key = self::KEY;
    public $name = "Varnish Purger";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = "Sends PURGE requests when a /post/view is updated";
}
