<?php declare(strict_types=1);

class StaticFilesInfo extends ExtensionInfo
{
    public const KEY = "static_files";

    public $key = self::KEY;
    public $name = "Static File Handler";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_HIDDEN;
    public $description = 'If Shimmie can\'t handle a request, check static files ($theme/static/$filename, then ext/static_files/static/$filename)';
    public $core = true;
}
