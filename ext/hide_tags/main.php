<?php

declare(strict_types=1);

namespace Shimmie2;

class HideTags extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config, $_shm_user_classes, $_shm_ratings;

        $config->set_default_bool(HideTagsConfig::ENABLED, true);
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Hide Tags");
        $sb->start_table();
        $sb->add_bool_option(HideTagsConfig::ENABLED, "Enable hiding tags", true);
        $sb->end_table();
    }
}
