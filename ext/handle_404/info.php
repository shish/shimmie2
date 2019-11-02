<?php

class Handle404Info extends ExtensionInfo
{
    public const KEY = "handle_404";

    public $key = self::KEY;
    public $name = "404 Detector";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = "If no other extension puts anything onto the page, show 404";
    public $core = true;
}
