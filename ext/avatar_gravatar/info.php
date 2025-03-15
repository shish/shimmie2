<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarGravatarInfo extends ExtensionInfo
{
    public const KEY = "avatar_gravatar";

    public string $key = self::KEY;
    public string $name = "Avatar Gravatar";
    public string $url = self::SHIMMIE_URL;
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Lets users use gravatar avatars as their avatar";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
