<?php

declare(strict_types=1);

namespace Shimmie2;

class XMLSitemapInfo extends ExtensionInfo
{
    public const KEY = "sitemap";

    public string $key = self::KEY;
    public string $name = "XML Sitemap";
    public string $url = "http://drudexsoftware.com";
    public array $authors = ["Sein Kraft" => "mail@seinkraft.info","Drudex Software" => "support@drudexsoftware.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Sitemap with caching & advanced priorities";
}
