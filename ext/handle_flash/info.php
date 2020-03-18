<?php declare(strict_types=1);

class FlashFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_flash";

    public $key = self::KEY;
    public $name = "Handle Flash";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Handle Flash files.";
}
