<?php

declare(strict_types=1);

namespace Shimmie2;

final class SVGFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_svg";

    public string $name = "SVG Graphics";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Handle static SVG files";
}
