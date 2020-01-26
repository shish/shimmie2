<?php declare(strict_types=1);

class ArrowkeyNavigationInfo extends ExtensionInfo
{
    public const KEY = "arrowkey_navigation";

    public $key = self::KEY;
    public $name = "Arrow Key Navigation";
    public $url = "http://www.drudexsoftware.com/";
    public $authors = ["Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allows viewers no navigate between images using the left & right arrow keys.";
    public $documentation =
"Simply enable this extension in the extension manager to enable arrow key navigation.";
}
