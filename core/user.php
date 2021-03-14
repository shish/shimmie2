<?php declare(strict_types=1);

function _new_user(array $row): User
{
    return new User($row);
}


/**
 * Class User
 *
 * An object representing a row in the "users" table.
 *
 * The currently logged in user will always be accessible via the global variable $user.
 */
class User
{
    public int $id;
    public string $name;
    public ?string $email;
    public string $join_date;
    public ?string $passhash;
    public UserClass $class;

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
    * Initialisation                                               *
    *                                                              *
    * User objects shouldn't be created directly, they should be   *
    * fetched from the database like so:                           *
    *                                                              *
    *    $user = User::by_name("bob");                             *
    * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * One will very rarely construct a user directly, more common
     * would be to use User::by_id, User::by_session, etc.
     *
     * @throws SCoreException
     */
    public function __construct(array $row)
    {
        global $_shm_user_classes;

        $this->id = int_escape((string)$row['id']);
        $this->name = $row['name'];
        $this->email = $row['email'];
        $this->join_date = $row['joindate'];
        $this->passhash = $row['pass'];

        if (array_key_exists($row["class"], $_shm_user_classes)) {
            $this->class = $_shm_user_classes[$row["class"]];
        } else {
            throw new SCoreException("User '{$this->name}' has invalid class '{$row["class"]}'");
        }
    }

    public static function by_session(string $name, string $session): ?User
    {
        global $cache, $config, $database;
        $row = $cache->get("user-session:$name-$session");
        if (!$row) {
            if ($database->get_driver_name() === DatabaseDriver::MYSQL) {
                $query = "SELECT * FROM users WHERE name = :name AND md5(concat(pass, :ip)) = :sess";
            } else {
                $query = "SELECT * FROM users WHERE name = :name AND md5(pass || :ip) = :sess";
            }
            $row = $database->get_row($query, ["name"=>$name, "ip"=>get_session_ip($config), "sess"=>$session]);
            $cache->set("user-session:$name-$session", $row, 600);
        }
        return is_null($row) ? null : new User($row);
    }

    public static function by_id(int $id): ?User
    {
        global $cache, $database;
        if ($id === 1) {
            $cached = $cache->get('user-id:'.$id);
            if ($cached) {
                return new User($cached);
            }
        }
        $row = $database->get_row("SELECT * FROM users WHERE id = :id", ["id"=>$id]);
        if ($id === 1) {
            $cache->set('user-id:'.$id, $row, 600);
        }
        return is_null($row) ? null : new User($row);
    }

    public static function by_name(string $name): ?User
    {
        global $database;
        $row = $database->get_row("SELECT * FROM users WHERE LOWER(name) = LOWER(:name)", ["name"=>$name]);
        return is_null($row) ? null : new User($row);
    }

    public static function name_to_id(string $name): int
    {
        $u = User::by_name($name);
        if (is_null($u)) {
            throw new ScoreException("Can't find any user named $name");
        } else {
            return $u->id;
        }
    }

    public static function by_name_and_pass(string $name, string $pass): ?User
    {
        $my_user = User::by_name($name);

        // If user tried to log in as "foo bar" and failed, try "foo_bar"
        if (!$my_user && str_contains($name, " ")) {
            $my_user = User::by_name(str_replace(" ", "_", $name));
        }

        if ($my_user) {
            if ($my_user->passhash == md5(strtolower($name) . $pass)) {
                log_info("core-user", "Migrating from md5 to bcrypt for $name");
                $my_user->set_password($pass);
            }
            if (password_verify($pass, $my_user->passhash)) {
                log_info("core-user", "Logged in as $name ({$my_user->class->name})");
                return $my_user;
            } else {
                log_warning("core-user", "Failed to log in as $name (Invalid password)");
            }
        } else {
            log_warning("core-user", "Failed to log in as $name (Invalid username)");
        }
        return null;
    }


    /* useful user object functions start here */

    public function can(string $ability): bool
    {
        return $this->class->can($ability);
    }


    public function is_anonymous(): bool
    {
        global $config;
        return ($this->id === $config->get_int('anon_id'));
    }

    public function is_logged_in(): bool
    {
        global $config;
        return ($this->id !== $config->get_int('anon_id'));
    }

    public function set_class(string $class): void
    {
        global $database;
        $database->execute("UPDATE users SET class=:class WHERE id=:id", ["class"=>$class, "id"=>$this->id]);
        log_info("core-user", 'Set class for '.$this->name.' to '.$class);
    }

    public function set_name(string $name): void
    {
        global $database;
        if (User::by_name($name)) {
            throw new ScoreException("Desired username is already in use");
        }
        $old_name = $this->name;
        $this->name = $name;
        $database->execute("UPDATE users SET name=:name WHERE id=:id", ["name"=>$this->name, "id"=>$this->id]);
        log_info("core-user", "Changed username for {$old_name} to {$this->name}");
    }

    public function set_password(string $password): void
    {
        global $database;
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if (is_string($hash)) {
            $this->passhash = $hash;
            $database->execute("UPDATE users SET pass=:hash WHERE id=:id", ["hash"=>$this->passhash, "id"=>$this->id]);
            log_info("core-user", 'Set password for '.$this->name);
        } else {
            throw new SCoreException("Failed to hash password");
        }
    }

    public function set_email(string $address): void
    {
        global $database;
        $database->execute("UPDATE users SET email=:email WHERE id=:id", ["email"=>$address, "id"=>$this->id]);
        log_info("core-user", 'Set email for '.$this->name);
    }

    /**
     * Get a snippet of HTML which will render the user's avatar, be that
     * a local file, a remote file, a gravatar, a something else, etc.
     */
    public function get_avatar_html(): string
    {
        // FIXME: configurable
        global $config;
        if ($config->get_string("avatar_host") === "gravatar") {
            if (!empty($this->email)) {
                $hash = md5(strtolower($this->email));
                $s = $config->get_string("avatar_gravatar_size");
                $d = urlencode($config->get_string("avatar_gravatar_default"));
                $r = $config->get_string("avatar_gravatar_rating");
                $cb = date("Y-m-d");
                return "<img alt='avatar' class=\"avatar gravatar\" src=\"https://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r&cacheBreak=$cb\">";
            }
        }
        return "";
    }

    /**
     * Get an auth token to be used in POST forms
     *
     * password = secret, avoid storing directly
     * passhash = bcrypt(password), so someone who gets to the database can't get passwords
     * sesskey  = md5(passhash . IP), so if it gets sniffed it can't be used from another IP,
     *            and it can't be used to get the passhash to generate new sesskeys
     * authtok  = md5(sesskey, salt), presented to the user in web forms, to make sure that
     *            the form was generated within the session. Salted and re-hashed so that
     *            reading a web page from the user's cache doesn't give access to the session key
     */
    public function get_auth_token(): string
    {
        global $config;
        $salt = DATABASE_DSN;
        $addr = get_session_ip($config);
        return md5(md5($this->passhash . $addr) . "salty-csrf-" . $salt);
    }

    public function get_auth_html(): string
    {
        $at = $this->get_auth_token();
        return '<input type="hidden" name="auth_token" value="'.$at.'">';
    }

    public function check_auth_token(): bool
    {
        if (defined("UNITTEST")) {
            return true;
        }
        return (isset($_POST["auth_token"]) && $_POST["auth_token"] == $this->get_auth_token());
    }

    public function ensure_authed(): void
    {
        if (!$this->check_auth_token()) {
            die("Invalid auth token");
        }
    }
}
