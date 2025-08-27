<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraImageFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_image_extra";

    public string $name = "Image Files++";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Convert various image formats to PNG or JPEG during upload";
}
