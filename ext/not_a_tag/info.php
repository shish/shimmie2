<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotATagInfo extends ExtensionInfo
{
    public const KEY = "not_a_tag";

    public string $name = "Not A Tag";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Redirect users to the rules if they use bad tags";
}
