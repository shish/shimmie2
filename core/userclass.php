<?php
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

    /**
     * @var ?string
     */
    public $name = null;

    /**
     * @var ?UserClass
     */
    public $parent = null;

    /**
     * @var array
     */
    public $abilities = [];

    public function __construct(string $name, string $parent=null, array $abilities=[])
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
            $val = $this->abilities[$ability];
            return $val;
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

// action_object_attribute
// action = create / view / edit / delete
// object = image / user / tag / setting
new UserClass("base", null, [
    Permissions::CHANGE_SETTING => false,  # modify web-level settings, eg the config table
    Permissions::OVERRIDE_CONFIG => false, # modify sys-level settings, eg shimmie.conf.php
    Permissions::BIG_SEARCH => false,      # search for more than 3 tags at once (speed mode only)

    Permissions::MANAGE_EXTENSION_LIST => false,
    Permissions::MANAGE_ALIAS_LIST => false,
    Permissions::MASS_TAG_EDIT => false,

    Permissions::VIEW_IP => false,         # view IP addresses associated with things
    Permissions::BAN_IP => false,

    Permissions::CREATE_USER => false,
    Permissions::EDIT_USER_NAME => false,
    Permissions::EDIT_USER_PASSWORD => false,
    Permissions::EDIT_USER_INFO => false,  # email address, etc
    Permissions::EDIT_USER_CLASS => false,
    Permissions::DELETE_USER => false,

    Permissions::CREATE_COMMENT => false,
    Permissions::DELETE_COMMENT => false,
    Permissions::BYPASS_COMMENT_CHECKS => false,  # spam etc

    Permissions::REPLACE_IMAGE => false,
    Permissions::CREATE_IMAGE => false,
    Permissions::EDIT_IMAGE_TAG => false,
    Permissions::EDIT_IMAGE_SOURCE => false,
    Permissions::EDIT_IMAGE_OWNER => false,
    Permissions::EDIT_IMAGE_LOCK => false,
    Permissions::EDIT_IMAGE_TITLE => false,
    Permissions::BULK_EDIT_IMAGE_TAG => false,
    Permissions::BULK_EDIT_IMAGE_SOURCE => false,
    Permissions::DELETE_IMAGE => false,

    Permissions::BAN_IMAGE => false,

    Permissions::VIEW_EVENTLOG => false,
    Permissions::IGNORE_DOWNTIME => false,

    Permissions::CREATE_IMAGE_REPORT => false,
    Permissions::VIEW_IMAGE_REPORT => false,  # deal with reported images

    Permissions::WIKI_ADMIN => false,
    Permissions::EDIT_WIKI_PAGE => false,
    Permissions::DELETE_WIKI_PAGE => false,

    Permissions::MANAGE_BLOCKS => false,

    Permissions::MANAGE_ADMINTOOLS => false,

    Permissions::VIEW_OTHER_PMS => false,
    Permissions::EDIT_FEATURE => false,
    Permissions::BULK_EDIT_VOTE => false,
    Permissions::EDIT_OTHER_VOTE => false,
    Permissions::VIEW_SYSINTO => false,

    Permissions::HELLBANNED => false,
    Permissions::VIEW_HELLBANNED => false,

    Permissions::PROTECTED => false,          # only admins can modify protected users (stops a moderator changing an admin's password)

    Permissions::EDIT_IMAGE_RATING => false,
    Permissions::BULK_EDIT_IMAGE_RATING => false,

    Permissions::VIEW_TRASH => false,

    Permissions::PERFORM_BULK_ACTIONS => false,

    Permissions::BULK_ADD => false,
    Permissions::EDIT_FILES => false,
    Permissions::EDIT_TAG_CATEGORIES => false,
    Permissions::RESCAN_MEDIA => false,
    Permissions::SEE_IMAGE_VIEW_COUNTS => false,

    Permissions::ARTISTS_ADMIN => false,
    Permissions::BLOTTER_ADMIN => false,
    Permissions::FORUM_ADMIN => false,
    Permissions::NOTES_ADMIN => false,
    Permissions::POOLS_ADMIN => false,
    Permissions::TIPS_ADMIN => false,
    Permissions::CRON_ADMIN => false,

    Permissions::APPROVE_IMAGE => false,
    Permissions::APPROVE_COMMENT => false,
]);

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
    Permissions::CREATE_IMAGE_REPORT => true,
    Permissions::EDIT_IMAGE_RATING => true,

]);

new UserClass("admin", "base", [
    Permissions::CHANGE_SETTING => true,
    Permissions::OVERRIDE_CONFIG => true,
    Permissions::BIG_SEARCH => true,
    Permissions::EDIT_IMAGE_LOCK => true,
    Permissions::VIEW_IP => true,
    Permissions::BAN_IP => true,
    Permissions::EDIT_USER_NAME => true,
    Permissions::EDIT_USER_PASSWORD => true,
    Permissions::EDIT_USER_INFO => true,
    Permissions::EDIT_USER_CLASS => true,
    Permissions::DELETE_USER => true,
    Permissions::CREATE_IMAGE => true,
    Permissions::DELETE_IMAGE => true,
    Permissions::BAN_IMAGE => true,
    Permissions::CREATE_COMMENT => true,
    Permissions::DELETE_COMMENT => true,
    Permissions::BYPASS_COMMENT_CHECKS => true,
    Permissions::REPLACE_IMAGE => true,
    Permissions::MANAGE_EXTENSION_LIST => true,
    Permissions::MANAGE_ALIAS_LIST => true,
    Permissions::EDIT_IMAGE_TAG => true,
    Permissions::EDIT_IMAGE_SOURCE => true,
    Permissions::EDIT_IMAGE_OWNER => true,
    Permissions::EDIT_IMAGE_TITLE => true,
    Permissions::BULK_EDIT_IMAGE_TAG => true,
    Permissions::BULK_EDIT_IMAGE_SOURCE => true,
    Permissions::MASS_TAG_EDIT => true,
    Permissions::CREATE_IMAGE_REPORT => true,
    Permissions::VIEW_IMAGE_REPORT => true,
    Permissions::WIKI_ADMIN => true,
    Permissions::EDIT_WIKI_PAGE => true,
    Permissions::DELETE_WIKI_PAGE => true,
    Permissions::VIEW_EVENTLOG => true,
    Permissions::MANAGE_BLOCKS => true,
    Permissions::MANAGE_ADMINTOOLS => true,
    Permissions::IGNORE_DOWNTIME => true,
    Permissions::VIEW_OTHER_PMS => true,
    Permissions::EDIT_FEATURE => true,
    Permissions::BULK_EDIT_VOTE => true,
    Permissions::EDIT_OTHER_VOTE => true,
    Permissions::VIEW_SYSINTO => true,
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
    Permissions::ARTISTS_ADMIN => true,
    Permissions::BLOTTER_ADMIN => true,
    Permissions::FORUM_ADMIN => true,
    Permissions::NOTES_ADMIN => true,
    Permissions::POOLS_ADMIN => true,
    Permissions::TIPS_ADMIN => true,
    Permissions::CRON_ADMIN => true,
    Permissions::APPROVE_IMAGE => true,
    Permissions::APPROVE_COMMENT => true,
]);

new UserClass("hellbanned", "user", [
    Permissions::HELLBANNED => true,
]);

@include_once "data/config/user-classes.conf.php";
