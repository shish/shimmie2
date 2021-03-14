<?php declare(strict_types=1);

class IcoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_ico";

    public string $key = self::KEY;
    public string $name = "Handle ICO";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Handle windows icons";
}
