<?php declare(strict_types=1);


class AutoCompleteConfig
{
    public const SEARCH_LIMIT = "autocomplete_search_limit";
    public const SEARCH_CATEGORIES = "autocomplete_search_categories";
    public const NAVIGATION = "autocomplete_navigation";
    public const TAGGING = "autocomplete_tagging";
}


class AutoComplete extends Extension
{
    /** @var AutoCompleteTheme */
    protected $theme;

    public function get_priority(): int
    {
        return 30;
    } // before Home

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_bool(AutoCompleteConfig::NAVIGATION, true);
        $config->set_default_bool(AutoCompleteConfig::TAGGING, false);
        $config->set_default_int(AutoCompleteConfig::SEARCH_LIMIT, 20);
        $config->set_default_bool(AutoCompleteConfig::SEARCH_CATEGORIES, false);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;

        $this->theme->build_autocomplete($page);
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $this->theme->display_admin_block($event);
    }
}
