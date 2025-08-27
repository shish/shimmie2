<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarPostInfo extends ExtensionInfo
{
    public const KEY = "avatar_post";

    public string $name = "Avatar Post";
    public array $authors = ["Mjokfox" => "mjokfox@findafox.net"];
    public string $description = "Lets users set a post as their avatar";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public array $dependencies = [UserConfigEditorInfo::KEY];
}
