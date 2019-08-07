<?php

/*
 * Name: XML Sitemap
 * Author: Sein Kraft <mail@seinkraft.info>
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://drudexsoftware.com
 * License: GPLv2
 * Description: Sitemap with caching & advanced priorities
 * Documentation:
 */

class XMLSitemapInfo extends ExtensionInfo
{
    public const KEY = "sitemap";

    public $key = self::KEY;
    public $name = "XML Sitemap";
    public $url = "http://drudexsoftware.com";
    public $authors = ["Sein Kraft"=>"mail@seinkraft.info","Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Sitemap with caching & advanced priorities";
}
