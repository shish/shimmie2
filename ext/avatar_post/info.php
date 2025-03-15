<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarPostInfo extends ExtensionInfo
{
    public const KEY = "avatar_post";

    public string $key = self::KEY;
    public string $name = "Avatar Post";
    public string $url = self::SHIMMIE_URL;
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Lets users set a post as their avatar";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
}
