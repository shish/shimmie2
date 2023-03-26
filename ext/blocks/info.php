<?php

declare(strict_types=1);

namespace Shimmie2;

class BlocksInfo extends ExtensionInfo
{
    public const KEY = "blocks";

    public string $key = self::KEY;
    public string $name = "Generic Blocks";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Add HTML to some space (News, Ads, etc)";
}
