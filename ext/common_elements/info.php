<?php

declare(strict_types=1);

namespace Shimmie2;

class CommonElementsInfo extends ExtensionInfo
{
    public const KEY = "common_elements";

    public string $key = self::KEY;
    public string $name = "Common Elements";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public bool $core = true;
    public string $description = "Renders common elements (thumbnails, paginators) in a way that themes can override";
}
