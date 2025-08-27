<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarGravatarInfo extends ExtensionInfo
{
    public const KEY = "avatar_gravatar";

    public string $name = "Avatars (Gravatar)";
    public array $authors = [self::SHISH_NAME => self::SHISH_EMAIL, "Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $description = "Lets users use gravatar avatars as their avatar";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
