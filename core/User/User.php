<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;

/**
 * Class User
 *
 * An object representing a row in the "users" table.
 *
 * The currently logged in user will always be accessible via the global variable $user.
 */
#[Type(name: "User")]
class User
{
    public int $id;
    #[Field]
    public string $name;
    public ?string $email;
    #[Field]
    public string $join_date;
    public ?string $passhash;
    #[Field]
    public UserClass $class;
    private ?Config $config = null;
    /** @var array<int, User> */
    private static array $by_id_cache = [];

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
     * @param array<string|int, mixed> $row
     */
    public function __construct(array $row)
    {
        $this->id = int_escape((string)$row['id']);
        $this->name = $row['name'];
        $this->email = $row['email'];
        $this->join_date = $row['joindate'];
        $this->passhash = $row['pass'];
        $this->class = UserClass::get_class($row['class']);
    }

    public function get_config(): Config
    {
        global $database;
        if (is_null($this->config)) {
            $this->config = new DatabaseConfig(
                $database,
                "user_config",
                "user_id",
                "{$this->id}",
                defaults: UserConfigGroup::get_all_defaults()
            );
        }
        return $this->config;
    }

    #[Query]
    public static function me(): User
    {
        global $user;
        return $user;
    }

    #[Field(name: "user_id")]
    public function graphql_oid(): int
    {
        return $this->id;
    }
    #[Field(name: "id")]
    public function graphql_guid(): string
    {
        return "user:{$this->id}";
    }


    public static function by_session(string $name, string $session): ?User
    {
        global $cache, $config, $database;
        $user = $cache->get("user-session-obj:$name-$session");
        if (is_null($user)) {
            try {
                $user_by_name = User::by_name($name);
            } catch (UserNotFound $e) {
                return null;
            }
            if ($user_by_name->get_session_id() === $session) {
                $user = $user_by_name;
            }
            // For 2.12, check old session IDs and convert to new IDs
            if (md5($user_by_name->passhash . Network::get_session_ip($config)) === $session) {
                $user = $user_by_name;
                $user->set_login_cookie();
            }
            $cache->set("user-session-obj:$name-$session", $user, 600);
        }
        return $user;
    }

    public static function by_id(int $id): User
    {
        global $cache, $database;
        if ($id === 1) {
            $cached = $cache->get('user-id:'.$id);
            if (!is_null($cached)) {
                return new User($cached);
            }
        }
        $row = $database->get_row("SELECT * FROM users WHERE id = :id", ["id" => $id]);
        if ($id === 1) {
            $cache->set('user-id:'.$id, $row, 600);
        }
        if (is_null($row)) {
            throw new UserNotFound("Can't find any user with ID $id");
        }
        return new User($row);
    }

    /**
     * Fetch a user with in-memory caching, which most internal systems
     * will ignore and get out-of-sync with the database. This should
     * only be used in situations where we are dealing with long lists
     * of read-only users where the same user might appear many times,
     * eg the comment list on an image page.
     */
    public static function by_id_dangerously_cached(int $id): User
    {
        if (!array_key_exists($id, self::$by_id_cache)) {
            self::$by_id_cache[$id] = User::by_id($id);
        }
        return self::$by_id_cache[$id];
    }

    #[Query(name: "user")]
    public static function by_name(string $name): User
    {
        global $database;
        $row = $database->get_row("SELECT * FROM users WHERE LOWER(name) = LOWER(:name)", ["name" => $name]);
        if (is_null($row)) {
            throw new UserNotFound("Can't find any user named $name");
        } else {
            return new User($row);
        }
    }

    public static function name_to_id(string $name): int
    {
        return User::by_name($name)->id;
    }

    public static function by_name_and_pass(string $name, string $pass): User
    {
        try {
            $my_user = User::by_name($name);
        } catch (UserNotFound $e) {
            // If user tried to log in as "foo bar" and failed, try "foo_bar"
            try {
                $my_user = User::by_name(str_replace(" ", "_", $name));
            } catch (UserNotFound $e) {
                Log::warning("core-user", "Failed to log in as $name (Invalid username)");
                throw $e;
            }
        }

        if ($my_user->passhash == md5(strtolower($name) . $pass)) {
            Log::info("core-user", "Migrating from md5 to bcrypt for $name");
            $my_user->set_password($pass);
        }
        assert(!is_null($my_user->passhash));
        if (password_verify($pass, $my_user->passhash)) {
            Log::info("core-user", "Logged in as $name ({$my_user->class->name})");
            return $my_user;
        } else {
            Log::warning("core-user", "Failed to log in as $name (Invalid password)");
            throw new UserNotFound("Can't find anybody with that username and password");
        }
    }


    /* useful user object functions start here */

    public function can(string $ability): bool
    {
        return $this->class->can($ability);
    }


    public function is_anonymous(): bool
    {
        global $config;
        return ($this->id === $config->get_int(UserAccountsConfig::ANON_ID));
    }

    public function set_class(string $class): void
    {
        global $database;
        $database->execute("UPDATE users SET class=:class WHERE id=:id", ["class" => $class, "id" => $this->id]);
        Log::info("core-user", 'Set class for '.$this->name.' to '.$class);
    }

    public function set_name(string $name): void
    {
        global $database;
        try {
            User::by_name($name);
            throw new InvalidInput("Desired username is already in use");
        } catch (UserNotFound $e) {
            // if user is not found, we're good
        }
        $old_name = $this->name;
        $this->name = $name;
        $database->execute("UPDATE users SET name=:name WHERE id=:id", ["name" => $this->name, "id" => $this->id]);
        Log::info("core-user", "Changed username for {$old_name} to {$this->name}");
    }

    public function set_password(string $password): void
    {
        global $database;
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->passhash = $hash;
        $database->execute("UPDATE users SET pass=:hash WHERE id=:id", ["hash" => $this->passhash, "id" => $this->id]);
        Log::info("core-user", 'Set password for '.$this->name);
    }

    public function set_email(string $address): void
    {
        global $database;
        $database->execute("UPDATE users SET email=:email WHERE id=:id", ["email" => $address, "id" => $this->id]);
        Log::info("core-user", 'Set email for '.$this->name);
    }

    /**
     * Get an auth token to be used in POST forms
     *
     * the token is based on
     * - the user's password, so that only this user can use the token
     * - the session IP, to reduce the blast radius of guessed passwords
     * - a salt known only to the server, so that clients or attackers
     *   can't generate their own tokens even if they know the first two
     */
    public function get_auth_token(): string
    {
        global $config;
        return hash("sha3-256", $this->passhash . Network::get_session_ip($config) . SysConfig::getSecret());
    }


    public function get_session_id(): string
    {
        global $config;
        return hash("sha3-256", $this->passhash . Network::get_session_ip($config) . SysConfig::getSecret());
    }

    public function set_login_cookie(): void
    {
        global $config, $page;

        $page->add_cookie(
            "user",
            $this->name,
            time() + 60 * 60 * 24 * 365,
            '/'
        );
        $page->add_cookie(
            "session",
            $this->get_session_id(),
            time() + 60 * 60 * 24 * $config->get_int(UserAccountsConfig::LOGIN_MEMORY),
            '/'
        );
    }

}
