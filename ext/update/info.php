<?php

declare(strict_types=1);

namespace Shimmie2;

class UpdateInfo extends ExtensionInfo
{
    public const KEY = "update";

    public string $key = self::KEY;
    public string $name = "Update";
    public string $url = "http://www.codeanimu.net";
    public array $authors = ["DakuTree" => "dakutree@codeanimu.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Shimmie updater!";
    public array $dependencies = [AdminPageInfo::KEY];
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
}
