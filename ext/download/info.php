<?php

class DownloadInfo extends ExtensionInfo
{
    public const KEY = "download";

    public string $key = self::KEY;
    public string $name = "Download";
    public array $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "System-wide download functions";
    public bool $core = true;
    public string $visibility = self::VISIBLE_HIDDEN;
}
