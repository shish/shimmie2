<?php

declare(strict_types=1);

namespace Shimmie2;

final class XMLSitemapInfo extends ExtensionInfo
{
    public const KEY = "sitemap";

    public string $name = "XML Sitemap";
    public array $authors = ["Sein Kraft" => "mail@seinkraft.info","Drudex Software" => "support@drudexsoftware.com"];
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Sitemap with caching & advanced priorities";
}
