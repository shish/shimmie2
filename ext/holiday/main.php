<?php

declare(strict_types=1);

namespace Shimmie2;

class Holiday extends Extension
{
    /** @var HolidayTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool("holiday_aprilfools", false);
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Holiday Theme");
        $sb->add_bool_option("holiday_aprilfools", "Enable April Fools");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config;
        if (date('d/m') == '01/04' && $config->get_bool("holiday_aprilfools")) {
            $this->theme->display_holiday("aprilfools");
        }
    }
}
