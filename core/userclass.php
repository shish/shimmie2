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
    public array $abilities = [];

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
}

$_all_false = [];
$_all_true = [];
foreach ((new \ReflectionClass(Permissions::class))->getConstants() as $k => $v) {
    assert(is_string($v));
    $_all_false[$v] = false;
    $_all_true[$v] = true;
}
// hellbanned is a snowflake, it isn't really a "permission" so much as
// "a special behaviour which applies to one particular user class"
$_all_true[Permissions::HELLBANNED] = false;
new UserClass("base", null, $_all_false);
new UserClass("admin", null, $_all_true);
unset($_all_true);
unset($_all_false);

// Ghost users can log in and do read-only stuff
// with their own account, but no writing
new UserClass("ghost", "base", [
    Permissions::READ_PM => true,
]);

// Anonymous users can't do anything except sign
// up to become regular users
new UserClass("anonymous", "base", [
    Permissions::CREATE_USER => true,
]);

// Users can control themselves, upload new content,
// and do basic edits (tags, source, title) on other
// people's content
new UserClass("user", "base", [
    Permissions::BIG_SEARCH => true,
    Permissions::CREATE_IMAGE => true,
    Permissions::CREATE_COMMENT => true,
    Permissions::EDIT_IMAGE_TAG => true,
    Permissions::EDIT_IMAGE_SOURCE => true,
    Permissions::EDIT_IMAGE_TITLE => true,
    Permissions::EDIT_IMAGE_RELATIONSHIPS => true,
    Permissions::EDIT_IMAGE_ARTIST => true,
    Permissions::CREATE_IMAGE_REPORT => true,
    Permissions::EDIT_IMAGE_RATING => true,
    Permissions::EDIT_FAVOURITES => true,
    Permissions::CREATE_VOTE => true,
    Permissions::SEND_PM => true,
    Permissions::READ_PM => true,
    Permissions::SET_PRIVATE_IMAGE => true,
    Permissions::PERFORM_BULK_ACTIONS => true,
    Permissions::BULK_DOWNLOAD => true,
    Permissions::CHANGE_USER_SETTING => true,
    Permissions::FORUM_CREATE => true,
    Permissions::NOTES_CREATE => true,
    Permissions::NOTES_EDIT => true,
    Permissions::NOTES_REQUEST => true,
    Permissions::POOLS_CREATE => true,
    Permissions::POOLS_UPDATE => true,
]);

// Hellbanning is a special case where a user can do all
// of the normal user actions, but their posts are hidden
// from everyone else
new UserClass("hellbanned", "user", [
    Permissions::HELLBANNED => true,
]);

// @phpstan-ignore-next-line
@include_once "data/config/user-classes.conf.php";
