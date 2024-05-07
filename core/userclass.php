<?php

declare(strict_types=1);

namespace Shimmie2;

use FFSPHP\PDO;

use GQLA\Type;
use GQLA\Field;

/**
 * Class UserClass
 */
#[Type(name: "UserClass")]
class UserClass
{
    /** @var array<string, UserClass> */
    public static array $known_classes = [];

    #[Field]
    public ?string $name = null;
    public ?UserClass $parent = null;
    public bool $core = false;

    /** @var array<string, bool> */
    public array $abilities = [];

    public function __construct(string $name)
    {
        global $database;

        $this->name = $name;
        $class = $database->execute("SELECT * FROM permissions WHERE class = :class", ["class" => $name])->fetch(PDO::FETCH_ASSOC);

        if (!is_null($class["parent"])) {
            $this->parent = static::$known_classes[$class["parent"]];
        }
        $this->core = (bool)$class["core"];

        unset($class["id"]);
        unset($class["class"]);
        unset($class["parent"]);
        unset($class["core"]);

        $this->abilities = $class;

        static::$known_classes[$name] = $this;
    }

    /**
     * @return string[]
     */
    #[Field(type: "[Permission!]!")]
    public function permissions(): array
    {
        $perms = [];
        foreach ((new \ReflectionClass(Permissions::class))->getConstants() as $k => $v) {
            if ($this->can($v)) {
                $perms[] = $v;
            }
        }
        return $perms;
    }

    /**
     * Determine if this class of user can perform an action or has ability.
     */
    public function can(string $ability): bool
    {
        // hellbanned is a snowflake, it isn't really a "permission" so much as
        // "a special behaviour which applies to one particular user class"
        if ($this->name == "admin" && $ability != "hellbanned") {
            return true;
        } elseif (array_key_exists($ability, $this->abilities) && $this->abilities[$ability]) {
            return true;
        } elseif (!is_null($this->parent)) {
            return $this->parent->can($ability);
        } elseif (array_key_exists($ability, $this->abilities)) {
            return false;
        } else {
            $min_dist = 9999;
            $min_ability = null;
            foreach (UserClass::$known_classes['base']->abilities as $a => $cando) {
                $v = levenshtein($ability, $a);
                if ($v < $min_dist) {
                    $min_dist = $v;
                    $min_ability = $a;
                }
            }
            throw new ServerError("Unknown ability '$ability'. Did the developer mean '$min_ability'?");
        }
    }

    // clear and load classes from the database.
    public static function loadClasses(): void
    {
        global $database;

        // clear any existing classes to avoid complications with parent classes
        foreach(static::$known_classes as $k => $v) {
            unset(static::$known_classes[$k]);
        }

        $classes = $database->get_col("SELECT class FROM permissions WHERE 1=1 ORDER BY id");
        foreach($classes as $class) {
            new UserClass($class);
        }
    }
}
