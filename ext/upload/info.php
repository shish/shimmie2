<?php

class UploadInfo extends ExtensionInfo
{
    public const KEY = "upload";

    public $key = self::KEY;
    public $name = "Uploader";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allows people to upload files to the website";
    public $core = true;
}
