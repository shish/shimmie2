<?php

declare(strict_types=1);

namespace Shimmie2;

final class BrowserSearchInfo extends ExtensionInfo
{
    public const KEY = "browser_search";

    public string $name = "Browser Search";
    public array $authors = ["ATravelingGeek" => "mailto:atg@atravelinggeek.com", "Artanis" => "mailto:artanis.00@gmail.com"];
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Allows the user to add a browser 'plugin' to search the site with real-time suggestions";
    public ?string $documentation =
        "Once installed, users with an opensearch compatible browser should see their search box light up with whatever \"click here to add a search engine\" notification they have";
}
