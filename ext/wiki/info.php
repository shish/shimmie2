<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiInfo extends ExtensionInfo
{
    public const KEY = "wiki";

    public string $key = self::KEY;
    public string $name = "Wiki";
    public string $url = self::SHIMMIE_URL;
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "Luana Latte" => "luana.latte.cat@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "A very simple built-in wiki";
}
