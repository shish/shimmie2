<?php

declare(strict_types=1);

namespace Shimmie2;

class UserAccountsConfig extends ConfigGroup
{
    public const ANON_ID = "anon_id";
    public const SIGNUP_ENABLED = "login_signup_enabled";
    public const SIGNUP_DISABLED_MESSAGE = "login_signup_disabled_message";
    public const LOGIN_MEMORY = "login_memory";
    public const LOGIN_TAC = "login_tac";
    public const LOGIN_TAC_BBCODE = "login_tac_bbcode";
    public const USER_EMAIL_REQUIRED = "user_email_required";
}

class AvatarConfig extends ConfigGroup
{
    public const HOST = "avatar_host";
    public const GRAVATAR_TYPE = "avatar_gravatar_type";
    public const GRAVATAR_SIZE = "avatar_gravatar_size";
    public const GRAVATAR_DEFAULT = "avatar_gravatar_default";
    public const GRAVATAR_RATING = "avatar_gravatar_rating";
    public const POST_AVATAR_ID = "avatar_post_id";
    public const POST_AVATAR_SCALE = "avatar_post_scale";
    public const POST_AVATAR_X = "avatar_post_x";
    public const POST_AVATAR_Y = "avatar_post_y";
}
