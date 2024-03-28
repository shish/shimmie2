<?php

declare(strict_types=1);

namespace Shimmie2;

/** @var Config */
global $user_config;


// The user object doesn't exist until after database setup operations and the first wave of InitExtEvents,
// so we can't reliably access this data until then. This event is triggered by the system after all of that is done.
class InitUserConfigEvent extends Event
{
    public User $user;
    public Config $user_config;

    public function __construct(User $user, Config $user_config)
    {
        parent::__construct();
        $this->user = $user;
        $this->user_config = $user_config;
    }
}


class UserOptionsBuildingEvent extends Event
{
    protected SetupTheme $theme;
    public SetupPanel $panel;
    public User $user;


    public function __construct(User $user, SetupPanel $panel)
    {
        parent::__construct();
        $this->user = $user;
        $this->panel = $panel;
    }
}

class UserConfig extends Extension
{
    /** @var UserConfigTheme */
    protected Themelet $theme;

    public const VERSION = "ext_user_config_version";
    public const ENABLE_API_KEYS = "ext_user_config_enable_api_keys";
    public const API_KEY = "api_key";

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool(self::ENABLE_API_KEYS, false);
    }

    public function onUserLogin(UserLoginEvent $event): void
    {
        global $user_config;

        $user_config = self::get_for_user($event->user->id);
    }

    public static function get_for_user(int $id): BaseConfig
    {
        global $database;

        $user = User::by_id($id);

        $user_config = new DatabaseConfig($database, "user_config", "user_id", "$id");
        send_event(new InitUserConfigEvent($user, $user_config));
        return $user_config;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version(self::VERSION) < 1) {
            $database->create_table("user_config", "
                user_id INTEGER NOT NULL,
                name VARCHAR(128) NOT NULL,
                value TEXT,
                PRIMARY KEY (user_id, name),
			    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		    ");
            $database->execute("CREATE INDEX user_config_user_id_idx ON user_config(user_id)");

            $this->set_version(self::VERSION, 1);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "user" && !$user->is_anonymous()) {
            $event->add_nav_link("user_config", new Link('user_config'), "User Options", false, 40);
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
        global $user, $database, $config, $page, $user_config;

        if ($config->get_bool(self::ENABLE_API_KEYS)) {
            if ($event->get_GET("api_key") && $user->is_anonymous()) {
                $user_id = $database->get_one(
                    "SELECT user_id FROM user_config WHERE value=:value AND name=:name",
                    ["value" => $event->get_GET("api_key"), "name" => self::API_KEY]
                );

                if (!empty($user_id)) {
                    $user = User::by_id($user_id);
                    if ($user !== null) {
                        send_event(new UserLoginEvent($user));
                    }
                }
            }

            if ($event->page_matches("user_admin/reset_api_key", method: "POST")) {
                $user_config->set_string(self::API_KEY, "");

                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            }
        }

        if ($event->page_matches("user_config", method: "GET", permission: Permissions::CHANGE_USER_SETTING)) {
            $uobe = send_event(new UserOptionsBuildingEvent($user, new SetupPanel($user_config)));
            $this->theme->display_user_config_page($page, $uobe->user, $uobe->panel);
        }
        if ($event->page_matches("user_config/save", method: "POST", permission: Permissions::CHANGE_USER_SETTING)) {
            $input = validate_input([
                'id' => 'user_id,exists'
            ]);
            $duser = User::by_id($input['id']);

            if ($user->id != $duser->id && !$user->can(Permissions::CHANGE_OTHER_USER_SETTING)) {
                throw new PermissionDenied("You do not have permission to change other user's settings");
            }

            $target_config = UserConfig::get_for_user($duser->id);
            send_event(new ConfigSaveEvent($target_config, $event->POST));
            $target_config->save();
            $page->flash("Config saved");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user_config"));
        }
    }

    public function onUserOperationsBuilding(UserOperationsBuildingEvent $event): void
    {
        global $config;

        if ($config->get_bool(self::ENABLE_API_KEYS)) {
            $key = $event->user_config->get_string(self::API_KEY, "");
            if (empty($key)) {
                $key = generate_key();
                $event->user_config->set_string(self::API_KEY, $key);
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
