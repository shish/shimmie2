<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\{Field, Type};

#[Type(name: "UserClass")]
final class UserClass
{
    /** @var array<string, UserClass> */
    public static array $known_classes = [];
    public static UserClassSource $loading = UserClassSource::UNKNOWN;
    /** @var list<UserClassSource> */
    public readonly array $sources;

    /**
     * @param array<string, bool> $abilities
     */
    public function __construct(
        #[Field]
        public readonly string $name,
        private readonly ?string $parent_name = null,
        private readonly array $abilities = [],
        public readonly string $description = "",
    ) {
        if (array_key_exists($name, self::$known_classes)) {
            if (self::$loading === UserClassSource::DEFAULT) {
                throw new ServerError("Duplicate default class: {$name}");
            }
            $this->sources = array_merge(self::$known_classes[$name]->sources, [self::$loading]);
        } else {
            $this->sources = [self::$loading];
        }
        self::$known_classes[$name] = $this;
    }

    public static function get_class(string $name): UserClass
    {
        if (array_key_exists($name, self::$known_classes)) {
            return self::$known_classes[$name];
        } else {
            Log::error("core-user", "User class '{$name}' does not exist - treating as anonymous");
            return self::$known_classes["anonymous"];
        }
    }

    public function get_parent(): ?UserClass
    {
        return static::$known_classes[$this->parent_name] ?? null;
    }

    // #[Field(type: "[Permission!]!")]
    /**
     * @return string[]
     */
    public function permissions(): array
    {
        $perms = [];
        foreach (PermissionGroup::get_subclasses() as $class) {
            foreach ($class->getConstants() as $_k => $v) {
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
        } elseif (!is_null($this->get_parent())) {
            return $this->get_parent()->can($ability);
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

    public function has_own_permission(string $permission): bool
    {
        return array_key_exists($permission, $this->abilities);
    }

    public function set_permission(string $permission, bool $value): void
    {
        if (!defined("UNITTEST")) {
            throw new ServerError("Cannot set permission '$permission' outside of unit tests.");
        }
        // create a new userclass with the same name but updated abilities
        new UserClass($this->name, $this->parent_name, $this->abilities + [$permission => $value], $this->description);
    }
}
