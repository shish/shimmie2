<?php declare(strict_types=1);

class UploadInfo extends ExtensionInfo
{
    public const KEY = "upload";

    public $key = self::KEY;
    public $name = "Uploader";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allows people to upload files to the website";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
