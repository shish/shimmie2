<?php

declare(strict_types=1);

namespace Shimmie2;

class TagEditCloudInfo extends ExtensionInfo
{
    public const KEY = "tag_editcloud";

    public string $key = self::KEY;
    public string $name = "Tag EditCloud";
    public array $authors = ["AtomicDryad" => null, "Luana Latte" => "luana.latte.cat@gmail.com"];
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Add or remove tags to the editor via clicking.";
}
