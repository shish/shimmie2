<?php

declare(strict_types=1);

namespace Shimmie2;

class EokmConfig extends ConfigGroup
{
    public ?string $title = "EOKM Filter";

    #[ConfigMeta("Username", ConfigType::STRING)]
    public const USERNAME = "eokm_username";

    #[ConfigMeta("Password", ConfigType::STRING)]
    public const PASSWORD = "eokm_password";
}
