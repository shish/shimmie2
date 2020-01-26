<?php declare(strict_types=1);

class RandomListInfo extends ExtensionInfo
{
    public const KEY = "random_list";

    public $key = self::KEY;
    public $name = "Random List";
    public $url = "http://www.drudexsoftware.com";
    public $authors = ["Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allows displaying a page with random images";
    public $documentation =
"Random image list can be accessed through www.yoursite.com/random
It is recommended that you create a link to this page so users know it exists.";
}
