<?php

declare(strict_types=1);

namespace Shimmie2;

final class HelpPagesInfo extends ExtensionInfo
{
    public const KEY = "help_pages";

    public string $name = "Help Pages";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides documentation screens";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public bool $core = true;
}
