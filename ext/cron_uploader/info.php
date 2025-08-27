<?php

declare(strict_types=1);

namespace Shimmie2;

final class CronUploaderInfo extends ExtensionInfo
{
    public const KEY = "cron_uploader";

    public string $name = "Cron Uploader";
    public array $authors = ["YaoiFox" => "admin@yaoifox.com", "Matthew Barbour" => "matthew@darkholme.net"];
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Uploads images automatically using Cron Jobs";
    public array $dependencies = [UserApiKeysInfo::KEY, UserConfigEditorInfo::KEY];
}
