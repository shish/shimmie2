<?php
/**
 * @global UserClass[] $_shm_user_classes
 */
global $_shm_user_classes;
$_shm_user_classes = array();

/**
 * Class UserClass
 */
class UserClass {

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
	public $abilities = array();

	/**
	 * @param string $name
	 * @param null|string $parent
	 * @param array $abilities
	 */
	public function __construct($name, $parent=null, $abilities=array()) {
		global $_shm_user_classes;

		$this->name = $name;
		$this->abilities = $abilities;

		if(!is_null($parent)) {
			$this->parent = $_shm_user_classes[$parent];
		}

		$_shm_user_classes[$name] = $this;
	}

	/**
	 * Determine if this class of user can perform an action or has ability.
	 *
	 * @param string $ability
	 * @return bool
	 * @throws SCoreException
	 */
	public function can(/*string*/ $ability) {
		if(array_key_exists($ability, $this->abilities)) {
			$val = $this->abilities[$ability];
			return $val;
		}
		else if(!is_null($this->parent)) {
			return $this->parent->can($ability);
		}
		else {
			global $_shm_user_classes;
			$min_dist = 9999;
			$min_ability = null;
			foreach($_shm_user_classes['base']->abilities as $a => $cando) {
				$v = levenshtein($ability, $a);
				if($v < $min_dist) {
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
new UserClass("base", null, array(
	"change_setting" => False,  # modify web-level settings, eg the config table
	"override_config" => False, # modify sys-level settings, eg shimmie.conf.php
	"big_search" => False,      # search for more than 3 tags at once (speed mode only)

	"manage_extension_list" => False,
	"manage_alias_list" => False,
	"mass_tag_edit" => False,

	"view_ip" => False,         # view IP addresses associated with things
	"ban_ip" => False,

	"edit_user_name" => False,
	"edit_user_password" => False,
	"edit_user_info" => False,  # email address, etc
	"edit_user_class" => False,
	"delete_user" => False,

	"create_comment" => False,
	"delete_comment" => False,
	"bypass_comment_checks" => False,  # spam etc

	"replace_image" => False,
	"create_image" => False,
	"edit_image_tag" => False,
	"edit_image_source" => False,
	"edit_image_owner" => False,
	"edit_image_lock" => False,
	"bulk_edit_image_tag" => False,
	"bulk_edit_image_source" => False,
	"delete_image" => False,

	"ban_image" => False,

	"view_eventlog" => False,
	"ignore_downtime" => False,

	"create_image_report" => False,
	"view_image_report" => False,  # deal with reported images

	"edit_wiki_page" => False,
	"delete_wiki_page" => False,

	"manage_blocks" => False,

	"manage_admintools" => False,

	"view_other_pms" => False,
	"edit_feature" => False,
	"bulk_edit_vote" => False,
	"edit_other_vote" => False,
	"view_sysinfo" => False,

	"hellbanned" => False,
	"view_hellbanned" => False,

	"protected" => False,          # only admins can modify protected users (stops a moderator changing an admin's password)
));

new UserClass("anonymous", "base", array(
));

new UserClass("user", "base", array(
	"big_search" => True,
	"create_image" => True,
	"create_comment" => True,
	"edit_image_tag" => True,
	"edit_image_source" => True,
	"create_image_report" => True,
));

new UserClass("admin", "base", array(
	"change_setting" => True,
	"override_config" => True,
	"big_search" => True,
	"edit_image_lock" => True,
	"view_ip" => True,
	"ban_ip" => True,
	"edit_user_name" => True,
	"edit_user_password" => True,
	"edit_user_info" => True,
	"edit_user_class" => True,
	"delete_user" => True,
	"create_image" => True,
	"delete_image" => True,
	"ban_image" => True,
	"create_comment" => True,
	"delete_comment" => True,
	"bypass_comment_checks" => True,
	"replace_image" => True,
	"manage_extension_list" => True,
	"manage_alias_list" => True,
	"edit_image_tag" => True,
	"edit_image_source" => True,
	"edit_image_owner" => True,
	"bulk_edit_image_tag" => True,
	"bulk_edit_image_source" => True,
	"mass_tag_edit" => True,
	"create_image_report" => True,
	"view_image_report" => True,
	"edit_wiki_page" => True,
	"delete_wiki_page" => True,
	"view_eventlog" => True,
	"manage_blocks" => True,
	"manage_admintools" => True,
	"ignore_downtime" => True,
	"view_other_pms" => True,
	"edit_feature" => True,
	"bulk_edit_vote" => True,
	"edit_other_vote" => True,
	"view_sysinfo" => True,
	"view_hellbanned" => True,
	"protected" => True,
));

new UserClass("hellbanned", "user", array(
	"hellbanned" => True,
));

@include_once "data/config/user-classes.conf.php";

