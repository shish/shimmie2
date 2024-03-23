<?php

declare(strict_types=1);

namespace Shimmie2;

class Filter extends Extension
{
    /** @var FilterTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string("filter_tags", "spoilers\nguro\nscat\nfurry -rating:s\n");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;
        $this->theme->addFilterBox();
        $page->add_html_header("<script>
        Array.from(document.getElementsByClassName('thumb')).forEach(function(post) {
            post.style.display='none';
        });</script>");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Filters");
        $sb->add_longtext_option("filter_tags", 'Default filtered tags');
        $sb->add_label("This controls the tags which are hidden by default. This feature currently requires JavaScript. Separate filters by line, or by commas. You can enter multiple tags per filter, as well as negative tags.");
    }

    public function onInitUserConfig(InitUserConfigEvent $event): void
    {
        global $config;
        $event->user_config->set_default_string("filter_tags", $config->get_string("filter_tags"));
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        global $user;

        $sb = $event->panel->create_new_block("Filters");
        $sb->add_longtext_option("filter_tags", 'Default filtered tags');
        $sb->add_label("This controls the tags which are hidden by default. This feature currently requires JavaScript. Separate filters by line, or by commas. You can enter multiple tags per filter, as well as negative tags.");
    }
}
