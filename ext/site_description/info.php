<?php declare(strict_types=1);

class SiteDescriptionInfo extends ExtensionInfo
{
    public const KEY = "site_description";

    public $key = self::KEY;
    public $name = "Site Description";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = "A description for search engines";
    public $documentation =
"This extension sets the \"description\" meta tag in the header of pages so that search engines can pick it up";
}
