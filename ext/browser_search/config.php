<?php

declare(strict_types=1);

namespace Shimmie2;

class BrowserSearchConfig extends ConfigGroup
{
    #[ConfigMeta("Search results order", ConfigType::STRING, options: [
        "Alphabetical" => "a",
        "Tag Count" => "t",
        "Disabled" => "n",
    ])]
    public const RESULTS_ORDER = "search_suggestions_results_order";
}
