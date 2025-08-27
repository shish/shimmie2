<?php

declare(strict_types=1);

namespace Shimmie2;

class TombstonesInfo extends ExtensionInfo
{
    public const KEY = "tombstones";

    public string $name = "Tombstones";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Provide a marker listing some details about deleted posts";
}
