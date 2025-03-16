<?php

declare(strict_types=1);

namespace Shimmie2;

final class FeaturedConfig extends ConfigGroup
{
    public const KEY = "featured";

    #[ConfigMeta("Featured Post ID", ConfigType::INT, advanced: true)]
    public const ID = "featured_id";
}
