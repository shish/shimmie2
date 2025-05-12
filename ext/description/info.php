<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageDescriptionInfo extends ExtensionInfo
{
    public const KEY = "image_description";

    public string $key = self::KEY;
    public string $name = "Image Description";
    public array $authors = ["xifize" => "xifize@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow images to have descriptions";
}
