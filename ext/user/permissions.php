<?php

declare(strict_types=1);

namespace Shimmie2;

class UserAccountsPermission extends PermissionGroup
{
    public const KEY = "user";

    public const CREATE_USER = "create_user";
    public const CREATE_OTHER_USER = "create_other_user";
    public const EDIT_USER_NAME = "edit_user_name";
    public const EDIT_USER_PASSWORD = "edit_user_password";
    /** Edit metadata about a user (eg email address) */
    public const EDIT_USER_INFO = "edit_user_info";
    public const EDIT_USER_CLASS = "edit_user_class";
    public const DELETE_USER = "delete_user";
    /** modify own user-level settings */
    public const CHANGE_USER_SETTING = "change_user_setting";
    public const CHANGE_OTHER_USER_SETTING = "change_other_user_setting";
    public const HELLBANNED = "hellbanned";
    public const VIEW_HELLBANNED = "view_hellbanned";
    /** only admins can modify protected users (stops a moderator from changing an admin's password) */
    public const PROTECTED = "protected";
}
