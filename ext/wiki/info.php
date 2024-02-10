<?php

declare(strict_types=1);

namespace Shimmie2;

class WikiInfo extends ExtensionInfo
{
    public const KEY = "wiki";

    public string $key = self::KEY;
    public string $name = "Wiki";
    public string $url = self::SHIMMIE_URL;
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "Luana Latte" => "luana.latte.cat@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "A simple wiki, for those who don't want the hugeness of mediawiki";
    public ?string $documentation = "Standard formatting APIs are used (This will be BBCode by default)";
}
