<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserAccountsConfig extends ConfigGroup
{
    public const KEY = "user";

    #[ConfigMeta("Anonymous ID", ConfigType::INT, advanced: true)]
    public const ANON_ID = "anon_id";

    #[ConfigMeta("Enable API keys", ConfigType::BOOL, default: false)]
    public const ENABLE_API_KEYS = "ext_user_config_enable_api_keys";

    #[ConfigMeta("Allow new signups", ConfigType::BOOL, default: true)]
    public const SIGNUP_ENABLED = "login_signup_enabled";

    #[ConfigMeta("Require email address", ConfigType::BOOL, default: false)]
    public const USER_EMAIL_REQUIRED = "user_email_required";

    #[ConfigMeta("Terms & Conditions", ConfigType::STRING, input: ConfigInput::TEXTAREA)]
    public const LOGIN_TAC = "login_tac";

    #[ConfigMeta("On log in", ConfigType::STRING, default: "previous", options: [
        "Send to user profile" => "profile",
        "Return to previous page" => "previous",
    ])]
    public const LOGIN_REDIRECT = "user_login_redirect";

    #[ConfigMeta("Message when signups disabled", ConfigType::STRING, input: ConfigInput::TEXTAREA, default: "The board admin has disabled the ability to sign up for new accounts", advanced: true)]
    public const SIGNUP_DISABLED_MESSAGE = "login_signup_disabled_message";

    #[ConfigMeta("Login duration (days)", ConfigType::INT, default: 365, advanced: true)]
    public const LOGIN_MEMORY = "login_memory";

    #[ConfigMeta("Use BBCode for login T&C", ConfigType::BOOL, default: true, advanced: true)]
    public const LOGIN_TAC_BBCODE = "login_tac_bbcode";

    #[ConfigMeta("Session hash mask", ConfigType::STRING, default: "255.255.0.0", advanced: true)]
    public const SESSION_HASH_MASK = "session_hash_mask";

    #[ConfigMeta("Purge cookie on logout", ConfigType::BOOL, default: false, advanced: true)]
    public const PURGE_COOKIE = "speed_hax_purge_cookie";
}
