<?php

declare(strict_types=1);

namespace Shimmie2;

include_once "events.php";

final class BulkImportExportInfo extends ExtensionInfo
{
    public const KEY = "bulk_import_export";

    public string $name = "Bulk Import/Export";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows bulk exporting/importing of images and associated data";
    public array $dependencies = [BulkActionsInfo::KEY];
}
