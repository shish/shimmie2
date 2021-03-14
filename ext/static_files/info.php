<?php declare(strict_types=1);

class StaticFilesInfo extends ExtensionInfo
{
    public const KEY = "static_files";

    public string $key = self::KEY;
    public string $name = "Static File Handler";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $visibility = self::VISIBLE_HIDDEN;
    public string $description = 'If Shimmie can\'t handle a request, check static files ($theme/static/$filename, then ext/static_files/static/$filename)';
    public bool $core = true;
}
