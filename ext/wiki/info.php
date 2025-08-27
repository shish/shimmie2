<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiInfo extends ExtensionInfo
{
    public const KEY = "wiki";

    public string $name = "Wiki";
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "Luana Latte" => "mailto:luana.latte.cat@gmail.com"];
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "A very simple built-in wiki";
}
