<?php

declare(strict_types=1);

namespace Shimmie2;

class UserAccountsConfig extends ConfigGroup
{
    #[ConfigMeta("Anonymous ID", ConfigType::INT, advanced: true)]
    public const ANON_ID = "anon_id";

    #[ConfigMeta("Enable API keys", ConfigType::BOOL)]
    public const ENABLE_API_KEYS = "ext_user_config_enable_api_keys";

    #[ConfigMeta("Allow new signups", ConfigType::BOOL)]
    public const SIGNUP_ENABLED = "login_signup_enabled";

    #[ConfigMeta("Require email address", ConfigType::BOOL)]
    public const USER_EMAIL_REQUIRED = "user_email_required";

    #[ConfigMeta("Terms & Conditions", ConfigType::STRING, ui_type: "longtext")]
    public const LOGIN_TAC = "login_tac";

    #[ConfigMeta("On log in", ConfigType::STRING, options: [
        "Send to user profile" => "profile",
        "Return to previous page" => "previous",
    ])]
    public const LOGIN_REDIRECT = "user_login_redirect";

    #[ConfigMeta("Message when signups disabled", ConfigType::STRING, advanced: true)]
    public const SIGNUP_DISABLED_MESSAGE = "login_signup_disabled_message";

    #[ConfigMeta("Login duration (days)", ConfigType::INT, advanced: true)]
    public const LOGIN_MEMORY = "login_memory";

    #[ConfigMeta("Use BBCode for Login T&C", ConfigType::BOOL, advanced: true)]
    public const LOGIN_TAC_BBCODE = "login_tac_bbcode";
}
