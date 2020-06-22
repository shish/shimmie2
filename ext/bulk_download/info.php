<?php declare(strict_types=1);


class BulkDownloadInfo extends ExtensionInfo
{
    public const KEY = "bulk_download";

    public $key = self::KEY;
    public $name = "Bulk Download";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Allows bulk downloading images.";
    public $dependencies = [BulkActionsInfo::KEY];
}
