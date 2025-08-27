<?php

declare(strict_types=1);

namespace Shimmie2;

final class GoogleAnalyticsInfo extends ExtensionInfo
{
    public const KEY = "google_analytics";

    public string $name = "Google Analytics";
    public array $authors = ["Drudex Software" => "mailto:support@drudexsoftware.com"];
    public string $description = "Integrates Google Analytics tracking";
    public ?string $documentation =
        "User has to enter their Google Analytics ID in the Board Config to use this extension.";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
