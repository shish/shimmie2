<?php

declare(strict_types=1);

namespace Shimmie2;

class PoolsInfo extends ExtensionInfo
{
    public const KEY = "pools";

    public string $key = self::KEY;
    public string $name = "Pools";
    public array $authors = ["Sein Kraft" => "mail@seinkraft.info", "jgen" => "jgen.tech@gmail.com", "Daku" => "admin@codeanimu.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Allow users to create groups of images and order them.";
    public ?string $documentation =
"This extension allows users to created named groups of images, and order the images within the group. Useful for related images like in a comic, etc.";
}
