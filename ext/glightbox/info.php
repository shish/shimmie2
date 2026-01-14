<?php

namespace Shimmie2;

final class GLightboxInfo extends ExtensionInfo
{
    public const string KEY = "glightbox";

    public string $name = "GLightbox";
    public array $authors = ["Luana Latte" => "mailto:luana.latte.cat@gmail.com"];
    public string $description = "Open images in a glightbox viewer";
    public array $conflicts = [ZoomToClickInfo::KEY];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public ?string $documentation = "GLightbox itself is licensed under MIT.";
    public ?string $link = "https://github.com/biati-digital/glightbox";
}
