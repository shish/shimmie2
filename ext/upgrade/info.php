<?php

/*
 * Name: Database Upgrader
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Keeps things happy behind the scenes
 * Visibility: admin
 */

class UpgradeInfo extends ExtensionInfo
{
    public const KEY = "upgrade";

    public $key = self::KEY;
    public $name = "Database Upgrader";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Keeps things happy behind the scenes";
    public $visibility = self::VISIBLE_ADMIN;
    public $core = true;
}
