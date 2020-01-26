<?php declare(strict_types=1);

class BulkRemoveInfo extends ExtensionInfo
{
    public const KEY = "bulk_remove";

    public $key = self::KEY;
    public $name = "Bulk Remove";
    public $beta = true;
    public $url = "http://www.drudexsoftware.com/";
    public $authors = ["Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allows admin to delete many images at once through Board Admin.";
}
