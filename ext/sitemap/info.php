<?php

declare(strict_types=1);

namespace Shimmie2;

final class XMLSitemapInfo extends ExtensionInfo
{
    public const KEY = "sitemap";

    public string $name = "XML Sitemap";
    public array $authors = ["Sein Kraft" => "mailto:mail@seinkraft.info","Drudex Software" => "mailto:support@drudexsoftware.com"];
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Sitemap with caching & advanced priorities";
}
