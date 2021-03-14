<?php declare(strict_types=1);
/**
 * @global UserClass[] $_shm_user_classes
 */
global $_shm_user_classes;
$_shm_user_classes = [];

/**
 * Class UserClass
 */
class UserClass
{
    public ?string $name = null;
    public ?UserClass $parent = null;
    public array $abilities = [];

    public function __construct(string $name, string $parent = null, array $abilities = [])
    {
        global $_shm_user_classes;

        $this->name = $name;
        $this->abilities = $abilities;

        if (!is_null($parent)) {
            $this->parent = $_shm_user_classes[$parent];
        }

        $_shm_user_classes[$name] = $this;
    }

    /**
     * Determine if this class of user can perform an action or has ability.
     *
     * @throws SCoreException
     */
    public function can(string $ability): bool
    {
        if (array_key_exists($ability, $this->abilities)) {
            return $this->abilities[$ability];
        } elseif (!is_null($this->parent)) {
            return $this->parent->can($ability);
        } else {
            global $_shm_user_classes;
            $min_dist = 9999;
            $min_ability = null;
            foreach ($_shm_user_classes['base']->abilities as $a => $cando) {
                $v = levenshtein($ability, $a);
                if ($v < $min_dist) {
                    $min_dist = $v;
                    $min_ability = $a;
                }
            }
            throw new SCoreException("Unknown ability '$ability'. Did the developer mean '$min_ability'?");
        }
    }
}

$_all_false = [];
foreach ((new ReflectionClass('Permissions'))->getConstants() as $k => $v) {
    $_all_false[$v] = false;
}
new UserClass("base", null, $_all_false);
unset($_all_false);

// Ghost users can't do anything
new UserClass("ghost", "base", [
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
    Permissions::SEND_PM => true,
    Permissions::READ_PM => true,
    Permissions::SET_PRIVATE_IMAGE => true,
    Permissions::BULK_DOWNLOAD => true,
    Permissions::CHANGE_USER_SETTING => true
]);

new UserClass("hellbanned", "user", [
    Permissions::HELLBANNED => true,
]);

new UserClass("admin", "base", [
    Permissions::CHANGE_SETTING => true,
    Permissions::CHANGE_USER_SETTING => true,
    Permissions::CHANGE_OTHER_USER_SETTING => true,
    Permissions::OVERRIDE_CONFIG => true,
    Permissions::BIG_SEARCH => true,

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

    Permissions::CREATE_COMMENT => true,
    Permissions::DELETE_COMMENT => true,
    Permissions::BYPASS_COMMENT_CHECKS => true,

    Permissions::REPLACE_IMAGE => true,
    Permissions::CREATE_IMAGE => true,
    Permissions::EDIT_IMAGE_TAG => true,
    Permissions::EDIT_IMAGE_SOURCE => true,
    Permissions::EDIT_IMAGE_OWNER => true,
    Permissions::EDIT_IMAGE_LOCK => true,
    Permissions::EDIT_IMAGE_TITLE => true,
    Permissions::EDIT_IMAGE_RELATIONSHIPS => true,
    Permissions::EDIT_IMAGE_ARTIST => true,
    Permissions::BULK_EDIT_IMAGE_TAG => true,
    Permissions::BULK_EDIT_IMAGE_SOURCE => true,
    Permissions::DELETE_IMAGE => true,

    Permissions::BAN_IMAGE => true,

    Permissions::VIEW_EVENTLOG => true,
    Permissions::IGNORE_DOWNTIME => true,
    Permissions::VIEW_REGISTRATIONS => true,

    Permissions::CREATE_IMAGE_REPORT => true,
    Permissions::VIEW_IMAGE_REPORT => true,

    Permissions::WIKI_ADMIN => true,
    Permissions::EDIT_WIKI_PAGE => true,
    Permissions::DELETE_WIKI_PAGE => true,

    Permissions::MANAGE_BLOCKS => true,

    Permissions::MANAGE_ADMINTOOLS => true,

    Permissions::SEND_PM => true,
    Permissions::READ_PM => true,
    Permissions::VIEW_OTHER_PMS => true, # hm
    Permissions::EDIT_FEATURE => true,
    Permissions::BULK_EDIT_VOTE => true,
    Permissions::EDIT_OTHER_VOTE => true,
    Permissions::VIEW_SYSINTO => true,

    Permissions::HELLBANNED => false,
    Permissions::VIEW_HELLBANNED => true,

    Permissions::PROTECTED => true,

    Permissions::EDIT_IMAGE_RATING => true,
    Permissions::BULK_EDIT_IMAGE_RATING => true,

    Permissions::VIEW_TRASH => true,

    Permissions::PERFORM_BULK_ACTIONS => true,

    Permissions::BULK_ADD => true,
    Permissions::EDIT_FILES => true,
    Permissions::EDIT_TAG_CATEGORIES => true,
    Permissions::RESCAN_MEDIA => true,
    Permissions::SEE_IMAGE_VIEW_COUNTS => true,

    Permissions::EDIT_FAVOURITES => true,

    Permissions::ARTISTS_ADMIN => true,
    Permissions::BLOTTER_ADMIN => true,
    Permissions::FORUM_ADMIN => true,
    Permissions::NOTES_ADMIN => true,
    Permissions::POOLS_ADMIN => true,
    Permissions::TIPS_ADMIN => true,
    Permissions::CRON_ADMIN => true,

    Permissions::APPROVE_IMAGE => true,
    Permissions::APPROVE_COMMENT => true,

    Permissions::CRON_RUN =>true,

    Permissions::BULK_IMPORT =>true,
    Permissions::BULK_EXPORT =>true,
    Permissions::BULK_DOWNLOAD => true,

    Permissions::SET_PRIVATE_IMAGE => true,
    Permissions::SET_OTHERS_PRIVATE_IMAGES => true,
]);

@include_once "data/config/user-classes.conf.php";
