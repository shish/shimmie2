<?php declare(strict_types=1);

class BlotterInfo extends ExtensionInfo
{
    public const KEY = "blotter";

    public $key = self::KEY;
    public $name = "Blotter";
    public $url = "http://seemslegit.com/";
    public $authors = ["Zach Hall"=>"zach@sosguy.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Displays brief updates about whatever you want on every page.
Colors and positioning can be configured to match your site's design.

Development TODO at https://github.com/zshall/shimmie2/issues";
}
