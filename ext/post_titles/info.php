<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTitlesInfo extends ExtensionInfo
{
    public const KEY = "post_titles";

    public string $name = "Post Titles";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Add titles to media posts";
}
