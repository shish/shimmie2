<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserApiKeysUserConfig extends UserConfigGroup
{
    public const KEY = "user_api_keys";

    #[ConfigMeta("API key", ConfigType::STRING, advanced: true)]
    public const API_KEY = "api_key";
}
