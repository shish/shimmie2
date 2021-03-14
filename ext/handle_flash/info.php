<?php declare(strict_types=1);

class FlashFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_flash";

    public string $key = self::KEY;
    public string $name = "Handle Flash";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Handle Flash files.";
}
