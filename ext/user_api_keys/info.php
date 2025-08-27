<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserApiKeysInfo extends ExtensionInfo
{
    public const KEY = "user_api_keys";

    public string $name = "User API Keys";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Allows users to use a key for API authentication";
}
