<?php

declare(strict_types=1);

namespace Shimmie2;

final class BiographyInfo extends ExtensionInfo
{
    public const KEY = "biography";

    public string $name = "User Bios";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allow users to write a bit about themselves";
    public array $dependencies = [UserConfigEditorInfo::KEY];
}
