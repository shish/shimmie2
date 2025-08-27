<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarPostInfo extends ExtensionInfo
{
    public const KEY = "avatar_post";

    public string $name = "Avatars (Posts)";
    public array $authors = ["Mjokfox" => "mailto:mjokfox@findafox.net"];
    public string $description = "Lets users set a post as their avatar";
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public array $dependencies = [UserConfigEditorInfo::KEY];
}
