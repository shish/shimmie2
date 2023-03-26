<?php

declare(strict_types=1);

namespace Shimmie2;

class SetupInfo extends ExtensionInfo
{
    public const KEY = "setup";

    public string $key = self::KEY;
    public string $name = "Board Config";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allows the site admin to configure the board to his or her taste";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
