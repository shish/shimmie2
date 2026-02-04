<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<BiographyTheme> */
final class Biography extends Extension
{
    public const KEY = "biography";

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $duser = $event->display_user;
        $bio = $duser->get_config()->get(BiographyConfig::BIOGRAPHY) ?? "";

        if (Ctx::$user->id === $duser->id || Ctx::$user->can(UserAccountsPermission::EDIT_USER_INFO)) {
            $this->theme->display_composer($duser, $bio);
        } else {
            $this->theme->display_biography($bio);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("user/{name}/biography", method: "POST")) {
            $duser = User::by_name($event->get_arg("name"));
            if (Ctx::$user->id === $duser->id || Ctx::$user->can(UserAccountsPermission::EDIT_USER_INFO)) {
                $bio = $event->POST->req('biography');
                Log::info("biography", "Set biography to $bio");
                $duser->get_config()->set(BiographyConfig::BIOGRAPHY, $bio);
                Ctx::$page->flash("Bio Updated");
                Ctx::$page->set_redirect(Url::referer_or());
            } else {
                throw new PermissionDenied("You do not have permission to edit this user's biography");
            }
        }
    }
}
