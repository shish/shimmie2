<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReCaptchaConfig extends ConfigGroup
{
    public const KEY = "recaptcha";

    #[ConfigMeta("Secret key", ConfigType::STRING)]
    public const RECAPTCHA_PRIVKEY = "api_recaptcha_privkey";

    #[ConfigMeta("Site key", ConfigType::STRING)]
    public const RECAPTCHA_PUBKEY = "api_recaptcha_pubkey";
}
