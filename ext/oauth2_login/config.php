<?php

declare(strict_types=1);

namespace Shimmie2;

final class OAuth2LoginConfig extends ConfigGroup
{
    public const KEY = "oauth2_login";

    #[ConfigMeta("Provider name", ConfigType::STRING, default: "OAuth2")]
    public const PROVIDER_NAME = "oauth2_login_provider_name";

    #[ConfigMeta("Client ID", ConfigType::STRING)]
    public const CLIENT_ID = "oauth2_login_client_id";

    #[ConfigMeta("Client secret", ConfigType::STRING, advanced: true)]
    public const CLIENT_SECRET = "oauth2_login_client_secret";

    #[ConfigMeta("Authorization URL", ConfigType::STRING)]
    public const AUTHORIZATION_URL = "oauth2_login_authorization_url";

    #[ConfigMeta("Token URL", ConfigType::STRING)]
    public const TOKEN_URL = "oauth2_login_token_url";

    #[ConfigMeta("Userinfo URL", ConfigType::STRING)]
    public const USERINFO_URL = "oauth2_login_userinfo_url";

    #[ConfigMeta("Scopes", ConfigType::STRING, default: "openid profile email")]
    public const SCOPES = "oauth2_login_scopes";

    #[ConfigMeta("Username fields", ConfigType::STRING, default: "preferred_username,name,nickname,fqn,email,sub")]
    public const USERNAME_FIELDS = "oauth2_login_username_fields";

    #[ConfigMeta("Email field", ConfigType::STRING, default: "email")]
    public const EMAIL_FIELD = "oauth2_login_email_field";

    #[ConfigMeta("Email verified field", ConfigType::STRING, default: "email_verified", advanced: true)]
    public const EMAIL_VERIFIED_FIELD = "oauth2_login_email_verified_field";

    #[ConfigMeta("Require verified email", ConfigType::BOOL, default: true, advanced: true)]
    public const REQUIRE_VERIFIED_EMAIL = "oauth2_login_require_verified_email";

    #[ConfigMeta("Create users automatically", ConfigType::BOOL, default: true)]
    public const AUTO_CREATE_USERS = "oauth2_login_auto_create_users";
}
