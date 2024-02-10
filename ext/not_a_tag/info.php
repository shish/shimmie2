<?php

declare(strict_types=1);

namespace Shimmie2;

class NotATagInfo extends ExtensionInfo
{
    public const KEY = "not_a_tag";

    public string $key = self::KEY;
    public string $name = "Not A Tag";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Redirect users to the rules if they use bad tags";
}
