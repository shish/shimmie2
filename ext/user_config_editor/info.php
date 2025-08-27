<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserConfigEditorInfo extends ExtensionInfo
{
    public const KEY = "user_config_editor";

    public string $name = "User-specific settings";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides system-wide support for user-specific settings";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
