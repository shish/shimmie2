<?php

declare(strict_types=1);

namespace Shimmie2;

final class CommentListInfo extends ExtensionInfo
{
    public const KEY = "comment";

    public string $name = "Post Comments";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Allow users to make comments on images";
    public bool $core = true;
}
