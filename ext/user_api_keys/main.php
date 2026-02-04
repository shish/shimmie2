<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<UserApiKeysTheme> */
final class UserApiKeys extends Extension
{
    public const KEY = "user_api_keys";

    #[EventListener(priority: 6)] // This needs to happen before any other events, but after db upgrade
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;

        if ($event->GET->get("api_key")) {
            $user_id = $database->get_one(
                "SELECT user_id FROM user_config WHERE value=:value AND name=:name",
                ["value" => $event->GET->get("api_key"), "name" => UserApiKeysUserConfig::API_KEY]
            );

            if (!empty($user_id)) {
                send_event(new UserLoginEvent(User::by_id($user_id)));
            }
        }

        if ($event->page_matches("user_admin/reset_api_key", method: "POST")) {
            Ctx::$user->get_config()->set(UserApiKeysUserConfig::API_KEY, "");
            Ctx::$page->set_redirect(make_link("user"));
        }
    }

    #[EventListener]
    public function onUserOperationsBuilding(UserOperationsBuildingEvent $event): void
    {
        $key = $event->user_config->get(UserApiKeysUserConfig::API_KEY);
        if (!$key) {
            $key = generate_key();
            $event->user_config->set(UserApiKeysUserConfig::API_KEY, $key);
        }
        $event->add_part($this->theme->get_user_operations($key));
    }
}
