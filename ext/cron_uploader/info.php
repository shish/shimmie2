<?php

declare(strict_types=1);

namespace Shimmie2;

final class CronUploaderInfo extends ExtensionInfo
{
    public const KEY = "cron_uploader";

    public string $name = "Cron Uploader";
    public array $authors = ["YaoiFox" => "mailto:admin@yaoifox.com", "Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Uploads images automatically using Cron Jobs";
    public array $dependencies = [UserApiKeysInfo::KEY, UserConfigEditorInfo::KEY];
}
