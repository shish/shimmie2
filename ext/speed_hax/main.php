<?php

declare(strict_types=1);

namespace Shimmie2;

class SpeedHax extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        $config->set_default_bool(SpeedHaxConfig::NO_AUTO_DB_UPGRADE, false);
        $config->set_default_bool(SpeedHaxConfig::CACHE_EVENT_LISTENERS, false);
        $config->set_default_bool(SpeedHaxConfig::PURGE_COOKIE, false);
        $config->set_default_bool(SpeedHaxConfig::RECENT_COMMENTS, false);
        $config->set_default_int(SpeedHaxConfig::BIG_SEARCH, 0);
        $config->set_default_bool(SpeedHaxConfig::LIMIT_COMPLEX, false);
        $config->set_default_bool(SpeedHaxConfig::FAST_PAGE_LIMIT, false);
        $config->set_default_bool(SpeedHaxConfig::CACHE_FIRST_FEW, false);
        $config->set_default_bool(SpeedHaxConfig::RSS_LIMIT, false);
    }
}
