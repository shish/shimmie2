<?php

declare(strict_types=1);

namespace Shimmie2;

class UserConfigUserConfig extends UserConfigGroup
{
    public const KEY = "user_config";

    #[ConfigMeta("API key", ConfigType::STRING, advanced: true)]
    public const API_KEY = "api_key";
}
