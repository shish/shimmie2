<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZoomToClickInfo extends ExtensionInfo
{
    public const KEY = "zoom_to_click";

    public string $name = "Zoom to Click";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $description = "Click on an image to zoom to that point";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
