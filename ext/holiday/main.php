<?php declare(strict_types=1);

class Holiday extends Extension
{
    /** @var HolidayTheme */
    protected $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_bool("holiday_aprilfools", false);
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Holiday Theme");
        $sb->add_bool_option("holiday_aprilfools", "Enable April Fools");
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config;
        $date = /*date('d/m') == '01/01' ||date('d/m') == '14/02' || */date('d/m') == '01/04'/* || date('d/m') == '24/12' || date('d/m') == '25/12' || date('d/m') == '31/12'*/;
        if ($date) {
            if ($config->get_bool("holiday_aprilfools")) {
                $this->theme->display_holiday($date);
            }
        }
    }
}
