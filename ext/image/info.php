<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageIOInfo extends ExtensionInfo
{
    public const KEY = "image";

    public string $name = "Post Manager";
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "jgen" => "mailto:jgen.tech@gmail.com"];
    public string $description = "Handle the image database";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public bool $core = true;
}
