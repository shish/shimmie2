<?php

declare(strict_types=1);

namespace Shimmie2;

class MimeSystemInfo extends ExtensionInfo
{
    public const KEY = "mime";

    public string $key = self::KEY;
    public string $name = "MIME";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides system mime-related functionality";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
