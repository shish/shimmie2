<?php

declare(strict_types=1);

namespace Shimmie2;

class SpeedHaxConfig extends ConfigGroup
{
    public const NO_AUTO_DB_UPGRADE = "speed_hax_no_auto_db_upgrade";
    public const CACHE_EVENT_LISTENERS = "speed_hax_cache_listeners";
    public const PURGE_COOKIE = "speed_hax_purge_cookie";
    public const RECENT_COMMENTS = "speed_hax_recent_comments";
    public const BIG_SEARCH = "speed_hax_big_search";
    public const LIMIT_COMPLEX = "speed_hax_limit_complex";
    public const FAST_PAGE_LIMIT = "speed_hax_fast_page_limit";
    public const CACHE_FIRST_FEW = "speed_hax_cache_first_few";
    public const RSS_LIMIT = "speed_hax_rss_limit";
}
