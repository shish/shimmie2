<?php

declare(strict_types=1);

namespace Shimmie2;

class UserPageInfo extends ExtensionInfo
{
    public const KEY = "user";

    public string $key = self::KEY;
    public string $name = "User Management";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allows people to sign up to the website";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
