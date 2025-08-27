<?php

declare(strict_types=1);

namespace Shimmie2;

final class PoolsInfo extends ExtensionInfo
{
    public const KEY = "pools";

    public string $name = "Pools";
    public array $authors = ["Sein Kraft" => "mailto:mail@seinkraft.info", "jgen" => "mailto:jgen.tech@gmail.com", "Daku" => "mailto:admin@codeanimu.net"];
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Allow users to create groups of images and order them";
    public ?string $documentation =
        "This extension allows users to create named groups of images, and order the images within the group. Useful for related images like in a comic, etc.";
}
