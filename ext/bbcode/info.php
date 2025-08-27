<?php

declare(strict_types=1);

namespace Shimmie2;

final class BBCodeInfo extends ExtensionInfo
{
    public const KEY = "bbcode";

    public string $key = self::KEY;
    public string $name = "BBCode";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public bool $core = true;
    public string $description = "Turns BBCode into HTML";
}
