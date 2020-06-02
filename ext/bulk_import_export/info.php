<?php declare(strict_types=1);

include_once "events.php";

class BulkImportExportInfo extends ExtensionInfo
{
    public const KEY = "bulk_import_export";

    public $key = self::KEY;
    public $name = "Bulk Import/Export";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Allows bulk exporting/importing of images and associated data.";
    public $dependencies = [BulkActionsInfo::KEY];
}
