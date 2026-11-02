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

        if ($event->GET->get("api_key") && $this->is_api_key_ip_allowed()) {
            $user_id = $database->get_one(
                "SELECT user_id FROM user_config WHERE value=:value AND name=:name",
                ["value" => $event->GET->get("api_key"), "name" => UserApiKeysUserConfig::API_KEY]
            );

            if (!empty($user_id)) {
                send_event(new UserLoginEvent(User::by_id($user_id)));
            }
        }

        if ($event->page_matches("user_admin/reset_api_key", method: "POST")) {
            $key = generate_key();
            Ctx::$user->get_config()->set(UserApiKeysUserConfig::API_KEY, $key);
            Ctx::$page->set_redirect(make_link("user"));
        }

        if ($event->page_matches("user_admin/delete_api_key", method: "POST")) {
            Ctx::$user->get_config()->delete(UserApiKeysUserConfig::API_KEY);
            Ctx::$page->set_redirect(make_link("user"));
        }
    }

    #[EventListener]
    public function onUserOperationsBuilding(UserOperationsBuildingEvent $event): void
    {
        if (Ctx::$user->id !== $event->user->id) {
            return;
        }

        $key = $event->user_config->get(UserApiKeysUserConfig::API_KEY);
        $event->add_part($this->theme->get_user_operations($key ?? ""));
    }

    private function is_api_key_ip_allowed(): bool
    {
        $allowed_ips = Ctx::$config->get(UserApiKeysConfig::ALLOWED_IPS);
        $client_ip = Network::get_real_ip();
        $ip_list = array_map('trim', explode(',', $allowed_ips));

        foreach ($ip_list as $ip_range) {
            try {
                if (IPRange::parse($ip_range)->contains($client_ip)) {
                    return true;
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        return false;
    }
}
