<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZoomToClickInfo extends ExtensionInfo
{
    public const KEY = "zoom_to_click";

    public string $key = self::KEY;
    public string $name = "Zoom to Click";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Click on an image to zoom to that point";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
