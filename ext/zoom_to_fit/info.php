<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZoomToFitInfo extends ExtensionInfo
{
    public const KEY = "zoom_to_fit";

    public string $name = "Zoom to Fit";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Fit images to screen width, width+height, or full-size";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
