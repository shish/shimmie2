<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZoomLightboxInfo extends ExtensionInfo
{
    public const KEY = "zoom_lightbox";

    public string $name = "Zoom Lightbox";
    public array $authors = ["Luana Latte" => "mailto:luana.latte.cat@gmail.com"];
    public string $description = "Open images in a glightbox viewer";
    public array $conflicts = [ZoomToClickInfo::KEY];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public ?string $documentation = "GLightbox itself is licensed under MIT.";
    public ?string $link = "https://github.com/biati-digital/glightbox";
}
