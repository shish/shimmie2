<?php

declare(strict_types=1);

namespace Shimmie2;

class BiographyInfo extends ExtensionInfo
{
    public const KEY = "biography";

    public string $key = self::KEY;
    public string $name = "User Bios";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Allow users to write a bit about themselves";
}
