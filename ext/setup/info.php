<?php

declare(strict_types=1);

namespace Shimmie2;

final class SetupInfo extends ExtensionInfo
{
    public const KEY = "setup";

    public string $name = "Board Config";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allows the site admin to configure the board to his or her taste";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
