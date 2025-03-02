<?php

declare(strict_types=1);

namespace Shimmie2;

class Biography extends Extension
{
    public const KEY = "biography";
    /** @var BiographyTheme */
    protected Themelet $theme;

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $page, $user;
        $duser = $event->display_user;
        $bio = $duser->get_config()->get_string("biography", "");

        if ($user->id == $duser->id || $user->can(UserAccountsPermission::EDIT_USER_INFO)) {
            $this->theme->display_composer($page, $duser, $bio);
        } else {
            $this->theme->display_biography($page, $bio);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("user/{name}/biography", method: "POST")) {
            $duser = User::by_name($event->get_arg("name"));
            if ($user->id == $duser->id || $user->can(UserAccountsPermission::EDIT_USER_INFO)) {
                $bio = $event->req_POST('biography');
                Log::info("biography", "Set biography to $bio");
                $duser->get_config()->set_string("biography", $bio);
                $page->flash("Bio Updated");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link()));
            } else {
                throw new PermissionDenied("You do not have permission to edit this user's biography");
            }
        }
    }
}
