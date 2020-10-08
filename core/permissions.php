<?php declare(strict_types=1);

// action_object_attribute
// action = create / view / edit / delete
// object = image / user / tag / setting
abstract class Permissions
{
    public const CHANGE_SETTING = "change_setting";  # modify web-level settings, eg the config table
    public const OVERRIDE_CONFIG = "override_config"; # modify sys-level settings, eg shimmie.conf.php
    public const CHANGE_USER_SETTING = "change_user_setting"; # modify own user-level settings
    public const CHANGE_OTHER_USER_SETTING = "change_other_user_setting"; # modify own user-level settings

    public const BIG_SEARCH = "big_search";      # search for more than 3 tags at once (speed mode only)

    public const MANAGE_EXTENSION_LIST = "manage_extension_list";
    public const MANAGE_ALIAS_LIST = "manage_alias_list";
    public const MANAGE_AUTO_TAG = "manage_auto_tag";
    public const MASS_TAG_EDIT = "mass_tag_edit";

    public const VIEW_IP = "view_ip";         # view IP addresses associated with things
    public const BAN_IP = "ban_ip";

    public const CREATE_USER = "create_user";
    public const CREATE_OTHER_USER = "create_other_user";
    public const EDIT_USER_NAME = "edit_user_name";
    public const EDIT_USER_PASSWORD = "edit_user_password";
    public const EDIT_USER_INFO = "edit_user_info";  # email address, etc
    public const EDIT_USER_CLASS = "edit_user_class";
    public const DELETE_USER = "delete_user";

    public const CREATE_COMMENT = "create_comment";
    public const DELETE_COMMENT = "delete_comment";
    public const BYPASS_COMMENT_CHECKS = "bypass_comment_checks";  # spam etc

    public const REPLACE_IMAGE = "replace_image";
    public const CREATE_IMAGE = "create_image";
    public const EDIT_IMAGE_TAG = "edit_image_tag";
    public const EDIT_IMAGE_SOURCE = "edit_image_source";
    public const EDIT_IMAGE_OWNER = "edit_image_owner";
    public const EDIT_IMAGE_LOCK = "edit_image_lock";
    public const EDIT_IMAGE_TITLE = "edit_image_title";
    public const EDIT_IMAGE_RELATIONSHIPS = "edit_image_relationships";
    public const EDIT_IMAGE_ARTIST = "edit_image_artist";
    public const BULK_EDIT_IMAGE_TAG = "bulk_edit_image_tag";
    public const BULK_EDIT_IMAGE_SOURCE = "bulk_edit_image_source";
    public const DELETE_IMAGE = "delete_image";

    public const BAN_IMAGE = "ban_image";

    public const VIEW_EVENTLOG = "view_eventlog";
    public const IGNORE_DOWNTIME = "ignore_downtime";
    public const VIEW_REGISTRATIONS = "view_registrations";

    public const CREATE_IMAGE_REPORT = "create_image_report";
    public const VIEW_IMAGE_REPORT = "view_image_report";  # deal with reported images

    public const WIKI_ADMIN = "wiki_admin";
    public const EDIT_WIKI_PAGE = "edit_wiki_page";
    public const DELETE_WIKI_PAGE = "delete_wiki_page";

    public const MANAGE_BLOCKS = "manage_blocks";

    public const MANAGE_ADMINTOOLS = "manage_admintools";

    public const SEND_PM = "send_pm";
    public const READ_PM = "read_pm";
    public const VIEW_OTHER_PMS = "view_other_pms";
    public const EDIT_FEATURE = "edit_feature";
    public const BULK_EDIT_VOTE = "bulk_edit_vote";
    public const EDIT_OTHER_VOTE = "edit_other_vote";
    public const VIEW_SYSINTO = "view_sysinfo";

    public const HELLBANNED = "hellbanned";
    public const VIEW_HELLBANNED = "view_hellbanned";

    public const PROTECTED = "protected";          # only admins can modify protected users (stops a moderator changing an admin's password)

    public const EDIT_IMAGE_RATING = "edit_image_rating";
    public const BULK_EDIT_IMAGE_RATING = "bulk_edit_image_rating";

    public const VIEW_TRASH = "view_trash";

    public const PERFORM_BULK_ACTIONS = "perform_bulk_actions";

    public const BULK_ADD = "bulk_add";
    public const EDIT_FILES = "edit_files";
    public const EDIT_TAG_CATEGORIES = "edit_tag_categories";
    public const RESCAN_MEDIA = "rescan_media";
    public const SEE_IMAGE_VIEW_COUNTS = "see_image_view_counts";

    public const EDIT_FAVOURITES = "edit_favourites";

    public const ARTISTS_ADMIN = "artists_admin";
    public const BLOTTER_ADMIN = "blotter_admin";
    public const FORUM_ADMIN = "forum_admin";
    public const NOTES_ADMIN = "notes_admin";
    public const POOLS_ADMIN = "pools_admin";
    public const TIPS_ADMIN = "tips_admin";
    public const CRON_ADMIN = "cron_admin";
    public const APPROVE_IMAGE = "approve_image";
    public const APPROVE_COMMENT = "approve_comment";

    public const SET_PRIVATE_IMAGE = "set_private_image";
    public const SET_OTHERS_PRIVATE_IMAGES = "set_others_private_images";

    public const CRON_RUN = "cron_run";
    public const BULK_IMPORT = "bulk_import";
    public const BULK_EXPORT = "bulk_export";
    public const BULK_DOWNLOAD = "bulk_download";
}
