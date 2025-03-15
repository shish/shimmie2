<?php

declare(strict_types=1);

namespace Shimmie2;

final class IndexConfig extends ConfigGroup
{
    public const KEY = "index";
    public ?string $title = "Post List";
    public ?int $position = 20;

    #[ConfigMeta("Posts per page", ConfigType::INT, default: 24)]
    public const IMAGES = "index_images";

    #[ConfigMeta("Post order", ConfigType::STRING, default: "id DESC", advanced: true)]
    public const ORDER = "index_order";

    #[ConfigMeta("Limit search to N results", ConfigType::INT, default: null, advanced: true, help: "Going deeper into search history requires exponential more CPU time")]
    public const SEARCH_RESULTS_LIMIT = "index_search_results_limit";

    #[ConfigMeta("Limits bots to simple searches", ConfigType::BOOL, default: false, advanced: true, help: "Bots can sometimes generate really complicated nonsense queries that use a lot of CPU time")]
    public const SIMPLE_BOTS_ONLY = "index_simple_bots_only";

    #[ConfigMeta(
        "Extra caching on first pages",
        ConfigType::BOOL,
        default: false,
        advanced: true,
        help: "The first 10 pages in the <code>post/list</code> index get extra caching.",
    )]
    public const CACHE_FIRST_FEW = "speed_hax_cache_first_few";

    #[ConfigMeta("Limit anonymous searches to N tags", ConfigType::INT, default: 0, advanced: true)]
    public const BIG_SEARCH = "speed_hax_big_search";

    #[ConfigMeta("Limit complex searches", ConfigType::BOOL, default: false, advanced: true)]
    public const LIMIT_COMPLEX = "speed_hax_limit_complex";
}
