<?php

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
        $this->user = $user;
        $this->user_config = $user_config;
    }
}

class UserConfig extends Extension
{
    private const VERSION = "ext_user_config_version";

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        if ($config->get_int(self::VERSION,0)<1) {
            $this->install();
        }
    }

    public function onUserLogin(UserLoginEvent $event)
    {
        global $database, $user_config;

        $user_config = new DatabaseConfig($database, "user_config", "user_id", $event->user->id);
        send_event(new InitUserConfigEvent($event->user, $user_config));
    }

    private function install(): void
    {
        global $config, $database;

        if ($config->get_int(self::VERSION,0) < 1) {

            log_info("upgrade", "Adding user config table");

            $database->create_table("user_config", "
                user_id INTEGER NOT NULL,
                name VARCHAR(128) NOT NULL,
                value TEXT,
                PRIMARY KEY (user_id, name),
			    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		    ");
            $database->execute("CREATE INDEX user_config_user_id_idx ON user_config(user_id)");

            $config->set_int(self::VERSION, 1);
        }
    }


    // This needs to happen before any other events, but after db upgrade
    public function get_priority(): int
    {
        return 6;
    }
}
