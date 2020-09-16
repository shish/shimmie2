<?php declare(strict_types=1);

class TranscodeVideoInfo extends ExtensionInfo
{
    public const KEY = "transcode_video";

    public $key = self::KEY;
    public $name = "Transcode Video";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Allows admins to automatically and manually transcode videos.";
    public $documentation ="Requires ffmpeg";
}
