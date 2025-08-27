<?php

declare(strict_types=1);

namespace Shimmie2;

final class CommentListInfo extends ExtensionInfo
{
    public const KEY = "comment";

    public string $key = self::KEY;
    public string $name = "Post Comments";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Allow users to make comments on images";
    public bool $core = true;
}
