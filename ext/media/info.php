<?php

/*
 * Name: Media
 * Author: Matthew Barbour <matthew@darkholme.net>
 * Description: Provides common functions and settings used for media operations.
 */

class MediaInfo extends ExtensionInfo
{
    public const KEY = "media";

    public $key = self::KEY;
    public $name = "Media";
    public $url = self::SHIMMIE_URL;
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides common functions and settings used for media operations.";
    public $core = true;
}
