<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;

#[Type(name: "UserClass")]
class UserClass
{
    /** @var array<string, UserClass> */
    public static array $known_classes = [];

    #[Field]
    public string $name;
    public ?UserClass $parent = null;

    /** @var array<string, bool> */
    private array $abilities = [];

    /**
     * @param array<string, bool> $abilities
     */
    public function __construct(string $name, ?string $parent = null, array $abilities = [])
    {
        $this->name = $name;
        $this->abilities = $abilities;

        if (!is_null($parent)) {
            $this->parent = static::$known_classes[$parent];
        }

        static::$known_classes[$name] = $this;
    }

    // #[Field(type: "[Permission!]!")]
    /**
     * @return string[]
     */
    public function permissions(): array
    {
        $perms = [];
        foreach (get_subclasses_of(PermissionGroup::class) as $class) {
            foreach ((new \ReflectionClass($class))->getConstants() as $k => $v) {
                if ($this->can($v)) {
                    $perms[] = $v;
                }
            }
        }
        return $perms;
    }

    /**
     * Determine if this class of user can perform an action or has ability.
     */
    public function can(string $ability): bool
    {
        if (array_key_exists($ability, $this->abilities)) {
            return $this->abilities[$ability];
        } elseif (!is_null($this->parent)) {
            return $this->parent->can($ability);
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

    public function hasOwnPermission(string $permission): bool
    {
        return array_key_exists($permission, $this->abilities);
    }

    public function setPermission(string $permission, bool $value): void
    {
        if (!defined("UNITTEST")) {
            throw new ServerError("Cannot set permission '$permission' outside of unit tests.");
        }
        $this->abilities[$permission] = $value;
    }
}
