<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageIOInfo extends ExtensionInfo
{
    public const KEY = "image";

    public string $key = self::KEY;
    public string $name = "Post Manager";
    public string $url = self::SHIMMIE_URL;
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "jgen" => "jgen.tech@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Handle the image database";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public bool $core = true;
}
