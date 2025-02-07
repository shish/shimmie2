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
