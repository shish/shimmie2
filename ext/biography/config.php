<?php

declare(strict_types=1);

namespace Shimmie2;

final class BiographyConfig extends UserConfigGroup
{
    public const KEY = "biography";

    #[ConfigMeta(
        "Biography",
        ConfigType::STRING,
        advanced: true,
    )]
    public const BIOGRAPHY = "biography";
}
