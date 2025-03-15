<?php

declare(strict_types=1);

namespace Shimmie2;

final class BrowserSearchConfig extends ConfigGroup
{
    public const KEY = "browser_search";

    #[ConfigMeta("Search results order", ConfigType::STRING, default: "a", options: [
        "Alphabetical" => "a",
        "Tag Count" => "t",
        "Disabled" => "n",
    ])]
    public const RESULTS_ORDER = "search_suggestions_results_order";
}
