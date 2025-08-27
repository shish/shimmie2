<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesInfo extends ExtensionInfo
{
    public const KEY = "tag_categories";

    public string $name = "Tag Categories";
    public array $authors = ["Daniel Oaks" => "mailto:danneh@danneh.net"];
    public string $description = "Let tags be split into 'categories', like Danbooru's tagging";
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
