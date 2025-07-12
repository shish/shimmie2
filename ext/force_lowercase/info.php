<?php

declare(strict_types=1);

namespace Shimmie2;

final class ForceLowercaseInfo extends ExtensionInfo
{
    public const KEY = "force_lowercase";

    public string $key = self::KEY;
    public string $name = "Force Lowercase";
    public array $authors = ["Discomrade" => ""];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "Forces tags to lowercase letters, similar to other booru softwares. Read the docs.";
    public ?string $documentation = "This does not change existing tags. Use the <a href='/admin#Misc_Admin_Toolsmain'>board admin tool</a> to set all existing tags to lowercase.";
}
