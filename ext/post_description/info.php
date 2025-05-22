<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostDescriptionInfo extends ExtensionInfo
{
    public const KEY = "post_description";

    public string $key = self::KEY;
    public string $name = "Post Description";
    public array $authors = ["xifize" => "xifize@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow posts to have descriptions";
}
