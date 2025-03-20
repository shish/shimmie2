<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserConfig extends Extension
{
    public const KEY = "user_config";
    /** @var UserConfigTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->create_table("user_config", "
                user_id INTEGER NOT NULL,
                name VARCHAR(128) NOT NULL,
                value TEXT,
                PRIMARY KEY (user_id, name),
			    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		    ");
            $database->execute("CREATE INDEX user_config_user_id_idx ON user_config(user_id)");
            $this->set_version(1);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "user" && !$user->is_anonymous()) {
            $event->add_nav_link(make_link('user_config'), "User Options", order: 40);
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if (!$user->is_anonymous()) {
            $event->add_link("User Options", make_link("user_config"), 40);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $database, $config, $page;

        // if API keys are enabled, then _any_ anonymous page request can
        // be an authed page request if the api_key is set
        if ($config->get_bool(UserAccountsConfig::ENABLE_API_KEYS)) {
            if ($event->get_GET("api_key") && $user->is_anonymous()) {
                $user_id = $database->get_one(
                    "SELECT user_id FROM user_config WHERE value=:value AND name=:name",
                    ["value" => $event->get_GET("api_key"), "name" => UserConfigUserConfig::API_KEY]
                );

                if (!empty($user_id)) {
                    $user = User::by_id($user_id);
                    send_event(new UserLoginEvent($user));
                }
            }

            if ($event->page_matches("user_admin/reset_api_key", method: "POST")) {
                $user->get_config()->set_string(UserConfigUserConfig::API_KEY, "");

                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            }
        }

        if ($event->page_matches("user_config", method: "GET", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $blocks = [];
            foreach (UserConfigGroup::get_subclasses() as $class) {
                $group = $class->newInstance();
                if ($group::is_enabled()) {
                    $block = $this->theme->config_group_to_block($user->get_config(), $group);
                    if ($block) {
                        $blocks[] = $block;
                    }
                }
            }
            $this->theme->display_user_config_page($blocks, $user);
        }
        if ($event->page_matches("user_config/save", method: "POST", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $input = validate_input([
                'id' => 'user_id,exists'
            ]);
            $duser = User::by_id($input['id']);

            if ($user->id !== $duser->id && !$user->can(UserAccountsPermission::CHANGE_OTHER_USER_SETTING)) {
                throw new PermissionDenied("You do not have permission to change other user's settings");
            }

            send_event(new ConfigSaveEvent($duser->get_config(), ConfigSaveEvent::postToSettings($event->POST)));
            $page->flash("Config saved");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user_config"));
        }
    }

    public function onUserOperationsBuilding(UserOperationsBuildingEvent $event): void
    {
        global $config;

        if ($config->get_bool(UserAccountsConfig::ENABLE_API_KEYS)) {
            $key = $event->user_config->get_string(UserConfigUserConfig::API_KEY, "");
            if (empty($key)) {
                $key = generate_key();
                $event->user_config->set_string(UserConfigUserConfig::API_KEY, $key);
            }
            $event->add_part($this->theme->get_user_operations($key));
        }
    }

    // This needs to happen before any other events, but after db upgrade
    public function get_priority(): int
    {
        return 6;
    }
}
