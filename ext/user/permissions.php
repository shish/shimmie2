<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserAccountsPermission extends PermissionGroup
{
    public const KEY = "user";

    #[PermissionMeta("Sign up for own account")]
    public const CREATE_USER = "create_user";

    #[PermissionMeta("Change own settings")]
    public const CHANGE_USER_SETTING = "change_user_setting";

    #[PermissionMeta("Create other users")]
    public const CREATE_OTHER_USER = "create_other_user";

    #[PermissionMeta("Edit other users' names")]
    public const EDIT_USER_NAME = "edit_user_name";

    #[PermissionMeta("Edit other users' passwords")]
    public const EDIT_USER_PASSWORD = "edit_user_password";

    #[PermissionMeta("Edit other users' info (eg email address)")]
    public const EDIT_USER_INFO = "edit_user_info";

    #[PermissionMeta("Edit other users' classes", advanced: true)]
    public const EDIT_USER_CLASS = "edit_user_class";

    #[PermissionMeta("Delete other users")]
    public const DELETE_USER = "delete_user";

    #[PermissionMeta("Change other users' settings")]
    public const CHANGE_OTHER_USER_SETTING = "change_other_user_setting";

    #[PermissionMeta("Protected", advanced: true, help: "Only admins can modify protected users (stops a moderator from changing an admin's password)")]
    public const PROTECTED = "protected";

    #[PermissionMeta("Skip signup CAPTCHA")]
    public const SKIP_SIGNUP_CAPTCHA = "bypass_signup_captcha";

    #[PermissionMeta("Skip login CAPTCHA")]
    public const SKIP_LOGIN_CAPTCHA = "bypass_login_captcha";

    #[PermissionMeta("Bypass content checks")]
    public const BYPASS_CONTENT_CHECKS = "bypass_content_checks";
}
