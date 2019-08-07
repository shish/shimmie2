<?php

/**
 * Name: Extension Manager
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Visibility: admin
 * Description: A thing for point & click extension management
 * Documentation:
 */

class ExtManagerInfo extends ExtensionInfo
{
    public const KEY = "ext_manager";

    public $key = self::KEY;
    public $name = "Extension Manager";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = "A thing for point & click extension management";
    public $documentation = "Allows the admin to view a list of all extensions and enable or disable them; also allows users to view the list of activated extensions and read their documentation";
    public $core = true;
}
