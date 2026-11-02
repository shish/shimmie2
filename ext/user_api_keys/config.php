<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserApiKeysUserConfig extends UserConfigGroup
{
    public const KEY = "user_api_keys";

    #[ConfigMeta("API key", ConfigType::STRING, advanced: true, sensitive: true)]
    public const API_KEY = "api_key";
}

final class UserApiKeysConfig extends ConfigGroup
{
    public const KEY = "user_api_keys";

    #[ConfigMeta(
        "API key login IP allowlist",
        ConfigType::STRING,
        advanced: true,
        default: "127.0.0.1/8,::1/128",
        help: "Comma-separated list of IP addresses or CIDR ranges allowed to use API key authentication.",
    )]
    public const ALLOWED_IPS = "allowed_ips";
}
