<?php declare(strict_types=1);

class Biography extends Extension
{
    /** @var BiographyTheme */
    protected ?Themelet $theme;

    public function onUserPageBuilding(UserPageBuildingEvent $event)
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

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user, $user_config;
        if ($event->page_matches("biography")) {
            if ($user->check_auth_token()) {
                $user_config->set_string("biography", $_POST['biography']);
                $page->flash("Bio Updated");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link()));
            }
        }
    }
}
