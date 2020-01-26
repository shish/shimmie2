<?php declare(strict_types=1);

class HolidayInfo extends ExtensionInfo
{
    public const KEY = "holiday";

    public $key = self::KEY;
    public $name = "Holiday Theme";
    public $url = "http://www.codeanimu.net";
    public $authors = ["DakuTree"=>"thedakutree@codeanimu.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Use an additional stylesheet on certain holidays";
}
