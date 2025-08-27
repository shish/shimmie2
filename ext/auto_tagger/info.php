<?php

declare(strict_types=1);

namespace Shimmie2;

final class AutoTaggerInfo extends ExtensionInfo
{
    public const KEY = "auto_tagger";

    public string $name = "Auto-Tagger";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Provides several automatic tagging functions";
}
