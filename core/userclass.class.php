<?php
$_user_classes = array();

class UserClass {
	var $name = null;
	var $parent = null;
	var $abilities = array();

	public function __construct($name, $parent=null, $abilities=array()) {
		$this->name = $name;
		$this->parent = $parent;
		$this->abilities = $abilities;
	}

	public function can(/*string*/ $ability) {
		global $config;

		if(array_key_exists($ability, $this->abilities)) {
			$val = $this->abilities[$ability];
			if(is_bool($val)) return $val;
			else return $config->get_bool($val, false);
		}
		else if(!is_null($this->parent)) {
			return $this->parent->can($ability);
		}
		else {
			die("Unknown ability: ".html_escape($ability));
		}
	}
}

$_user_class_base = new UserClass("base", null, array(
	"change_setting" => False,  # web-level settings, eg the config table
	"override_config" => False, # sys-level config, eg config.php
	"big_search" => False,      # more than 3 tags (speed mode only)
	"lock_image" => False,
	"view_ip" => False,         # view IP addresses associated with things
	"ban_ip" => False,
	"change_password" => False,
	"change_user_info" => False,
	"delete_user" => False,
	"delete_image" => False,
	"delete_comment" => False,
	"replace_image" => False,
	"manage_extension_list" => False,
	"manage_alias_list" => False,
	"edit_image_tag" => False,
	"edit_image_source" => False,
	"edit_image_owner" => False,
	"mass_tag_edit" => False,
	"report_image" => False,
	"view_image_report" => False,
	"protected" => False,
));
$_user_classes["anonymous"] = new UserClass("anonymous", $_user_class_base, array(
	"edit_image_tag" => "tag_edit_anon",
	"edit_image_source" => "source_edit_anon",
	"report_image" => "report_image_anon",
));
$_user_classes["user"] = new UserClass("user", $_user_class_base, array(
	"big_search" => True,
	"edit_image_tag" => True,
	"edit_image_source" => True,
	"report_image" => True,
));
$_user_classes["admin"] = new UserClass("admin", $_user_class_base, array(
	"change_setting" => True,
	"override_config" => True,
	"big_search" => True,
	"lock_image" => True,
	"view_ip" => True,
	"ban_ip" => True,
	"change_password" => True,
	"change_user_info" => True,
	"delete_user" => True,
	"delete_image" => True,
	"delete_comment" => True,
	"replace_image" => True,
	"manage_extension_list" => True,
	"manage_alias_list" => True,
	"edit_image_tag" => True,
	"edit_image_source" => True,
	"edit_image_owner" => True,
	"mass_tag_edit" => True,
	"report_image" => True,
	"view_image_report" => True,
	"protected" => True,
));

foreach(unserialize(EXTRA_USER_CLASSES) as $class_info) {
	$name = $class_info[0];
	$base = $_user_classes[$class_info[1]];
	$abilities = $class_info[2];
	$_user_classes[$name] = new UserClass($name, $base, $abilities);
}
?>
