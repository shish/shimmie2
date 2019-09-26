<?php

/**
 * Name: Static File Handler
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Visibility: admin
 * Description: If Shimmie can't handle a request, check static files ($theme/static/$filename, then ext/handle_static/static/$filename)
 */

class HandleStaticInfo extends ExtensionInfo
{
    public const KEY = "handle_static";

    public $key = self::KEY;
    public $name = "Static File Handler";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = 'If Shimmie can\'t handle a request, check static files ($theme/static/$filename, then ext/handle_static/static/$filename)';
    public $core = true;
}
