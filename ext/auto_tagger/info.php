<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoTaggerInfo extends ExtensionInfo
{
    public const KEY = "auto_tagger";

    public string $key = self::KEY;
    public string $name = "Auto-Tagger";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Provides several automatic tagging functions";
}
