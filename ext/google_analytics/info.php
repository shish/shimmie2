<?php

declare(strict_types=1);

namespace Shimmie2;

class GoogleAnalyticsInfo extends ExtensionInfo
{
    public const KEY = "google_analytics";

    public string $key = self::KEY;
    public string $name = "Google Analytics";
    public string $url = "http://drudexsoftware.com";
    public array $authors = ["Drudex Software" => "support@drudexsoftware.com"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Integrates Google Analytics tracking";
    public ?string $documentation =
"User has to enter their Google Analytics ID in the Board Config to use this extension.";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
