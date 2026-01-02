<?php

declare(strict_types=1);

namespace Shimmie2;

include_once "events.php";

final class ForumInfo extends ExtensionInfo
{
    public const KEY = "forum";

    public string $name = "Forum";
    public array $authors = ["Sein Kraft" => "mailto:mail@seinkraft.info","Alpha" => "mailto:alpha@furries.com.ar"];
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Rough forum extension";
}
