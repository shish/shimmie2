<?php

declare(strict_types=1);

namespace Shimmie2;

class ETServerInfo extends ExtensionInfo
{
    public const KEY = "et_server";

    public string $key = self::KEY;
    public string $name = "System Info Registry";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Keep track of shimmie registrations";
    public ?string $documentation = "For internal use";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
