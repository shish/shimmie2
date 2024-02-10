<?php

declare(strict_types=1);

namespace Shimmie2;

class PrivateImageInfo extends ExtensionInfo
{
    public const KEY = "private_image";

    public string $key = self::KEY;
    public string $name = "Private Post";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allows users to mark images as private, which prevents other users from seeing them.";
}
