<?php

declare(strict_types=1);

namespace Shimmie2;

class Biography extends Extension
{
    /** @var BiographyTheme */
    protected Themelet $theme;

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $page, $user;
        $duser = $event->display_user;
        $duser_config = UserConfig::get_for_user($event->display_user->id);
        $bio = $duser_config->get_string("biography", "");

        if ($user->id == $duser->id) {
            $this->theme->display_composer($page, $bio);
        } else {
            $this->theme->display_biography($page, $bio);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user, $user_config;
        if ($event->page_matches("biography", method: "POST")) {
            $bio = $event->get_POST('biography');
            log_info("biography", "Set biography to $bio");
            $user_config->set_string("biography", $bio);
            $page->flash("Bio Updated");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(referer_or(make_link()));
        }
    }
}
