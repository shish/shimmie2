<?php

declare(strict_types=1);

namespace Shimmie2;

final class ForumInfo extends ExtensionInfo
{
    public const KEY = "forum";

    public string $name = "Forum";
    public array $authors = ["Sein Kraft" => "mail@seinkraft.info","Alpha" => "alpha@furries.com.ar"];
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Rough forum extension";
}
