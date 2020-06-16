<?php

class DownloadInfo extends ExtensionInfo
{
    public const KEY = "download";

    public $key = self::KEY;
    public $name = "Download";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "System-wide download functions";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
