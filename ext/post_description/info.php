<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostDescriptionInfo extends ExtensionInfo
{
    public const KEY = "post_description";

    public string $name = "Post Description";
    public array $authors = ["xifize" => "mailto:xifize@gmail.com"];
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow posts to have descriptions";
}
