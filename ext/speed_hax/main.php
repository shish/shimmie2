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

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Speed Hax");
        $sb->start_table();
        $sb->add_bool_option(SpeedHaxConfig::NO_AUTO_DB_UPGRADE, "Don't auto-upgrade database: ", false);
        $sb->add_bool_option(SpeedHaxConfig::CACHE_EVENT_LISTENERS, "<br>Cache event listeners: ", false);
        $sb->add_bool_option(SpeedHaxConfig::PURGE_COOKIE, "<br>Purge cookie on logout: ", false);
        $sb->add_bool_option(SpeedHaxConfig::RECENT_COMMENTS, "<br>List only recent comments: ", false);
        $sb->add_int_option(SpeedHaxConfig::BIG_SEARCH, "<br>Anonymous search tag limit: ", false);
        $sb->add_bool_option(SpeedHaxConfig::LIMIT_COMPLEX, "<br>Limit complex searches: ", false);
        $sb->add_bool_option(SpeedHaxConfig::FAST_PAGE_LIMIT, "<br>Fast page limit: ", false);
        $sb->add_bool_option(SpeedHaxConfig::CACHE_FIRST_FEW, "<br>Extra caching on first pages: ", false);
        $sb->add_bool_option(SpeedHaxConfig::RSS_LIMIT, "<br>Limit images RSS: ", false);
        $sb->end_table();
    }
}
