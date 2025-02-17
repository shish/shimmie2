<?php

declare(strict_types=1);

namespace Shimmie2;

class SpeedHaxConfig extends ConfigGroup
{
    public const KEY = "speed_hax";

    #[ConfigMeta("Don't auto-upgrade database", ConfigType::BOOL, default: false)]
    public const NO_AUTO_DB_UPGRADE = "speed_hax_no_auto_db_upgrade";

    #[ConfigMeta("Cache event listeners", ConfigType::BOOL, default: false)]
    public const CACHE_EVENT_LISTENERS = "speed_hax_cache_listeners";

    #[ConfigMeta("Purge cookie on logout", ConfigType::BOOL, default: false)]
    public const PURGE_COOKIE = "speed_hax_purge_cookie";

    #[ConfigMeta("List only recent comments", ConfigType::BOOL, default: false)]
    public const RECENT_COMMENTS = "speed_hax_recent_comments";

    #[ConfigMeta("Anonymous search tag limit", ConfigType::INT, default: 0)]
    public const BIG_SEARCH = "speed_hax_big_search";

    #[ConfigMeta("Limit complex searches", ConfigType::BOOL, default: false)]
    public const LIMIT_COMPLEX = "speed_hax_limit_complex";

    #[ConfigMeta("Fast page limit", ConfigType::BOOL, default: false)]
    public const FAST_PAGE_LIMIT = "speed_hax_fast_page_limit";

    #[ConfigMeta("Extra caching on first pages", ConfigType::BOOL, default: false)]
    public const CACHE_FIRST_FEW = "speed_hax_cache_first_few";

    #[ConfigMeta("Limit images RSS", ConfigType::BOOL, default: false)]
    public const RSS_LIMIT = "speed_hax_rss_limit";
}
