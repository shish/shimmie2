<?php

declare(strict_types=1);

namespace Shimmie2;

class SystemInfo extends ExtensionInfo
{
    public const KEY = "system";

    public string $key = self::KEY;
    public string $name = "System";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides system screen";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
