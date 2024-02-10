<?php

declare(strict_types=1);

namespace Shimmie2;

class SiteDescriptionInfo extends ExtensionInfo
{
    public const KEY = "site_description";

    public string $key = self::KEY;
    public string $name = "Site Description";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "A description for search engines";
    public ?string $documentation =
"This extension sets the \"description\" meta tag in the header of pages so that search engines can pick it up";
}
