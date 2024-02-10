<?php

declare(strict_types=1);

namespace Shimmie2;

class RegenThumbInfo extends ExtensionInfo
{
    public const KEY = "regen_thumb";

    public string $key = self::KEY;
    public string $name = "Regen Thumb";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Regenerate a thumbnail image";
    public ?string $documentation =
"This adds a button in the post control section on a post's view page, which allows an admin to regenerate
a post's thumbnail; useful for instance if the first attempt failed due to lack of memory, and memory has
since been increased.";
}
