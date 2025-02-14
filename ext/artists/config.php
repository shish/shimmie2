<?php

declare(strict_types=1);

namespace Shimmie2;

class ArtistsConfig extends ConfigGroup
{
    #[ConfigMeta("Artists per page", ConfigType::INT)]
    public const ARTISTS_PER_PAGE = "artistsPerPage";
}
