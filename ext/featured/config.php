<?php

declare(strict_types=1);

namespace Shimmie2;

class FeaturedConfig extends ConfigGroup
{
    #[ConfigMeta("Featured Post ID", ConfigType::INT, advanced: true)]
    public const ID = "featured_id";
}
