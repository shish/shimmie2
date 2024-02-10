<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkDownloadInfo extends ExtensionInfo
{
    public const KEY = "bulk_download";

    public string $key = self::KEY;
    public string $name = "Bulk Download";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows bulk downloading images.";
    public array $dependencies = [BulkActionsInfo::KEY];
}
