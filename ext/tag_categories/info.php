<?php

declare(strict_types=1);

namespace Shimmie2;

class TagCategoriesInfo extends ExtensionInfo
{
    public const KEY = "tag_categories";

    public string $key = self::KEY;
    public string $name = "Tag Categories";
    public string $url = "https://code.shishnet.org/shimmie2/";
    public array $authors = ["Daniel Oaks" => "danneh@danneh.net"];
    public string $description = "Let tags be split into 'categories', like Danbooru's tagging";
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
