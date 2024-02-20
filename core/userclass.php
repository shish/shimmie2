<?php

declare(strict_types=1);

namespace Shimmie2;

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

    /** @var array<string, bool> */
    public array $abilities = [];

    /**
     * @param array<string, bool> $abilities
     */
    public function __construct(string $name, string $parent = null, array $abilities = [])
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
        global $_all_false;
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
foreach ((new \ReflectionClass(Permissions::class))->getConstants() as $k => $v) {
    assert(is_string($v));
    $_all_false[$v] = false;
}
new UserClass("base", null, $_all_false);
unset($_all_false);

// Ghost users can't do anything
new UserClass("ghost", "base", [
    Permissions::READ_PM => true,
]);

// Anonymous users can't do anything by default, but
// the admin might grant them some permissions
new UserClass("anonymous", "base", [
    Permissions::CREATE_USER => true,
]);

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
    Permissions::FORUM_CREATE_THREAD => true,
    Permissions::NOTES_CREATE => true,
    Permissions::NOTES_EDIT => true,
    Permissions::POOLS_CREATE => true,
    Permissions::POOLS_UPDATE => true,
]);

new UserClass("hellbanned", "user", [
    Permissions::HELLBANNED => true,
]);

new UserClass("admin", "user", [
    Permissions::CHANGE_SETTING => true,
    Permissions::CHANGE_OTHER_USER_SETTING => true,
    Permissions::OVERRIDE_CONFIG => true,

    Permissions::MANAGE_EXTENSION_LIST => true,
    Permissions::MANAGE_ALIAS_LIST => true,
    Permissions::MANAGE_AUTO_TAG => true,
    Permissions::MASS_TAG_EDIT => true,

    Permissions::VIEW_IP => true,
    Permissions::BAN_IP => true,

    Permissions::CREATE_USER => true,
    Permissions::CREATE_OTHER_USER => true,
    Permissions::EDIT_USER_NAME => true,
    Permissions::EDIT_USER_PASSWORD => true,
    Permissions::EDIT_USER_INFO => true,
    Permissions::EDIT_USER_CLASS => true,
    Permissions::DELETE_USER => true,

    Permissions::DELETE_COMMENT => true,
    Permissions::BYPASS_COMMENT_CHECKS => true,

    Permissions::REPLACE_IMAGE => true,
    Permissions::EDIT_IMAGE_OWNER => true,
    Permissions::EDIT_IMAGE_LOCK => true,
    Permissions::BULK_EDIT_IMAGE_TAG => true,
    Permissions::BULK_EDIT_IMAGE_SOURCE => true,
    Permissions::DELETE_IMAGE => true,

    Permissions::BAN_IMAGE => true,

    Permissions::VIEW_EVENTLOG => true,
    Permissions::IGNORE_DOWNTIME => true,
    Permissions::VIEW_REGISTRATIONS => true,

    Permissions::VIEW_IMAGE_REPORT => true,

    Permissions::WIKI_ADMIN => true,
    Permissions::EDIT_WIKI_PAGE => true,
    Permissions::DELETE_WIKI_PAGE => true,

    Permissions::MANAGE_BLOCKS => true,

    Permissions::MANAGE_ADMINTOOLS => true,

    Permissions::VIEW_OTHER_PMS => true, # hm
    Permissions::EDIT_FEATURE => true,
    Permissions::BULK_EDIT_VOTE => true,
    Permissions::EDIT_OTHER_VOTE => true,
    Permissions::VIEW_SYSINFO => true,

    Permissions::HELLBANNED => false,
    Permissions::VIEW_HELLBANNED => true,

    Permissions::PROTECTED => true,

    Permissions::BULK_EDIT_IMAGE_RATING => true,

    Permissions::VIEW_TRASH => true,

    Permissions::BULK_ADD => true,
    Permissions::EDIT_FILES => true,
    Permissions::EDIT_TAG_CATEGORIES => true,
    Permissions::RESCAN_MEDIA => true,
    Permissions::SEE_IMAGE_VIEW_COUNTS => true,

    Permissions::ARTISTS_ADMIN => true,
    Permissions::BLOTTER_ADMIN => true,
    Permissions::FORUM_ADMIN => true,
    Permissions::NOTES_ADMIN => true,
    Permissions::POOLS_ADMIN => true,
    Permissions::TIPS_ADMIN => true,
    Permissions::CRON_ADMIN => true,

    Permissions::APPROVE_IMAGE => true,
    Permissions::APPROVE_COMMENT => true,
    Permissions::BYPASS_IMAGE_APPROVAL => true,

    Permissions::CRON_RUN => true,

    Permissions::BULK_IMPORT => true,
    Permissions::BULK_EXPORT => true,
    Permissions::BULK_PARENT_CHILD => true,

    Permissions::SET_OTHERS_PRIVATE_IMAGES => true,
]);

@include_once "data/config/user-classes.conf.php";
