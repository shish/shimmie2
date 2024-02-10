<?php

declare(strict_types=1);

namespace Shimmie2;

class BrowserSearchInfo extends ExtensionInfo
{
    public const KEY = "browser_search";

    public string $key = self::KEY;
    public string $name = "Browser Search";
    public string $url = "http://atravelinggeek.com/";
    public array $authors = ["ATravelingGeek" => "atg@atravelinggeek.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Allows the user to add a browser 'plugin' to search the site with real-time suggestions";
    public ?string $documentation =
"Once installed, users with an opensearch compatible browser should see their search box light up with whatever \"click here to add a search engine\" notification they have
<br>
<br>Some code (and lots of help) by Artanis (<a href='mailto:artanis.00@gmail.com'>Erik Youngren</a>) from the 'tagger' extension - Used with permission";
}
