<?php

declare(strict_types=1);

namespace Shimmie2;

class FourOhFourInfo extends ExtensionInfo
{
    public const KEY = "four_oh_four";

    public string $key = self::KEY;
    public string $name = "404 Detector";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public string $description = "If no other extension puts anything onto the page, show 404";
    public bool $core = true;
}
