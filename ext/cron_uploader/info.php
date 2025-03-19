<?php

declare(strict_types=1);

namespace Shimmie2;

final class CronUploaderInfo extends ExtensionInfo
{
    public const KEY = "cron_uploader";

    public string $key = self::KEY;
    public string $name = "Cron Uploader";
    public string $url = self::SHIMMIE_URL;
    public array $authors = ["YaoiFox" => "admin@yaoifox.com", "Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Uploads images automatically using Cron Jobs";
}
