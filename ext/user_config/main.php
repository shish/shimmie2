<?php declare(strict_types=1);

/** @var $user_config Config */
global $user_config;


// The user object doesn't exist until after database setup operations and the first wave of InitExtEvents,
// so we can't reliably access this data until then. This event is triggered by the system after all of that is done.
class InitUserConfigEvent extends Event
{
    public $user;
    public $user_config;

    public function __construct(User $user, Config $user_config)
    {
        parent::__construct();
        $this->user = $user;
        $this->user_config = $user_config;
    }
}

class UserConfig extends Extension
{
    /** @var UserConfigTheme */
    protected $theme;

    public const VERSION = "ext_user_config_version";
    public const ENABLE_API_KEYS = "ext_user_config_enable_api_keys";
    public const API_KEY = "api_key";

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_bool(self::ENABLE_API_KEYS, false);
    }

    public function onUserLogin(UserLoginEvent $event)
    {
        global $database, $user_config;

        $user_config = new DatabaseConfig($database, "user_config", "user_id", "{$event->user->id}");
        send_event(new InitUserConfigEvent($event->user, $user_config));
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

    public function onPageRequest(PageRequestEvent $event)
    {
        global $user, $database, $config, $page;

        if ($config->get_bool(self::ENABLE_API_KEYS)) {
            if (!empty($_GET["api_key"]) && $user->is_anonymous()) {
                $user_id = $database->get_one(
                    "SELECT user_id FROM user_config WHERE value=:value AND name=:name",
                    ["value" => $_GET["api_key"], "name" => self::API_KEY]
                );

                if (!empty($user_id)) {
                    $user = User::by_id($user_id);
                    if ($user !== null) {
                        send_event(new UserLoginEvent($user));
                    }
                }
            }

            global $user_config;

            if ($event->page_matches("user_admin")) {
                if (!$user->check_auth_token()) {
                    return;
                }
                switch ($event->get_arg(0)) {
                    case "reset_api_key":
                        $user_config->set_string(self::API_KEY, "");

                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("user"));

                        break;
                }
            }
        }
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event)
    {
        global $config, $user_config;

        if ($config->get_bool(self::ENABLE_API_KEYS)) {
            $key = $user_config->get_string(self::API_KEY, "");
            if (empty($key)) {
                $key = generate_key();
                $user_config->set_string(self::API_KEY, $key);
            }
            $event->add_html($this->theme->get_user_options($key));
        }
    }


    // This needs to happen before any other events, but after db upgrade
    public function get_priority(): int
    {
        return 6;
    }
}
