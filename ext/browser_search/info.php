<?php declare(strict_types=1);

class BrowserSearchInfo extends ExtensionInfo
{
    public const KEY = "browser_search";

    public $key = self::KEY;
    public $name = "Browser Search";
    public $url = "http://atravelinggeek.com/";
    public $authors = ["ATravelingGeek"=>"atg@atravelinggeek.com"];
    public $license = self::LICENSE_GPLV2;
    public $version = "0.1c, October 26, 2007";
    public $description = "Allows the user to add a browser 'plugin' to search the site with real-time suggestions";
    public $documentation =
"Once installed, users with an opensearch compatible browser should see their search box light up with whatever \"click here to add a search engine\" notification they have

Some code (and lots of help) by Artanis (Erik Youngren <artanis.00@gmail.com>) from the 'tagger' extension - Used with permission";
}
