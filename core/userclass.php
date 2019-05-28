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
     * @var null|string
     */
    public $name = null;

    /**
     * @var \UserClass|null
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
            throw new SCoreException("Unknown ability '".html_escape($ability)."'. Did the developer mean '".html_escape($min_ability)."'?");
        }
    }
}

// action_object_attribute
// action = create / view / edit / delete
// object = image / user / tag / setting
new UserClass("base", null, [
    "change_setting" => false,  # modify web-level settings, eg the config table
    "override_config" => false, # modify sys-level settings, eg shimmie.conf.php
    "big_search" => false,      # search for more than 3 tags at once (speed mode only)

    "manage_extension_list" => false,
    "manage_alias_list" => false,
    "mass_tag_edit" => false,

    "view_ip" => false,         # view IP addresses associated with things
    "ban_ip" => false,

    "edit_user_name" => false,
    "edit_user_password" => false,
    "edit_user_info" => false,  # email address, etc
    "edit_user_class" => false,
    "delete_user" => false,

    "create_comment" => false,
    "delete_comment" => false,
    "bypass_comment_checks" => false,  # spam etc

    "replace_image" => false,
    "create_image" => false,
    "edit_image_tag" => false,
    "edit_image_source" => false,
    "edit_image_owner" => false,
    "edit_image_lock" => false,
    "bulk_edit_image_tag" => false,
    "bulk_edit_image_source" => false,
    "delete_image" => false,

    "ban_image" => false,

    "view_eventlog" => false,
    "ignore_downtime" => false,

    "create_image_report" => false,
    "view_image_report" => false,  # deal with reported images

    "edit_wiki_page" => false,
    "delete_wiki_page" => false,

    "manage_blocks" => false,

    "manage_admintools" => false,

    "view_other_pms" => false,
    "edit_feature" => false,
    "bulk_edit_vote" => false,
    "edit_other_vote" => false,
    "view_sysinfo" => false,

    "hellbanned" => false,
    "view_hellbanned" => false,

    "protected" => false,          # only admins can modify protected users (stops a moderator changing an admin's password)
]);

new UserClass("anonymous", "base", [
]);

new UserClass("user", "base", [
    "big_search" => true,
    "create_image" => true,
    "create_comment" => true,
    "edit_image_tag" => true,
    "edit_image_source" => true,
    "create_image_report" => true,
]);

new UserClass("admin", "base", [
    "change_setting" => true,
    "override_config" => true,
    "big_search" => true,
    "edit_image_lock" => true,
    "view_ip" => true,
    "ban_ip" => true,
    "edit_user_name" => true,
    "edit_user_password" => true,
    "edit_user_info" => true,
    "edit_user_class" => true,
    "delete_user" => true,
    "create_image" => true,
    "delete_image" => true,
    "ban_image" => true,
    "create_comment" => true,
    "delete_comment" => true,
    "bypass_comment_checks" => true,
    "replace_image" => true,
    "manage_extension_list" => true,
    "manage_alias_list" => true,
    "edit_image_tag" => true,
    "edit_image_source" => true,
    "edit_image_owner" => true,
    "bulk_edit_image_tag" => true,
    "bulk_edit_image_source" => true,
    "mass_tag_edit" => true,
    "create_image_report" => true,
    "view_image_report" => true,
    "edit_wiki_page" => true,
    "delete_wiki_page" => true,
    "view_eventlog" => true,
    "manage_blocks" => true,
    "manage_admintools" => true,
    "ignore_downtime" => true,
    "view_other_pms" => true,
    "edit_feature" => true,
    "bulk_edit_vote" => true,
    "edit_other_vote" => true,
    "view_sysinfo" => true,
    "view_hellbanned" => true,
    "protected" => true,
]);

new UserClass("hellbanned", "user", [
    "hellbanned" => true,
]);

@include_once "data/config/user-classes.conf.php";
