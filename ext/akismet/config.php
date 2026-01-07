<?php

declare(strict_types=1);

namespace Shimmie2;

final class AkismetConfig extends ConfigGroup
{
    public const KEY = "akismet";

    #[ConfigMeta(
        "Akismet API key",
        ConfigType::STRING,
        help: "Get your API key from https://akismet.com/"
    )]
    public const API_KEY = "comment_wordpress_key";
}
