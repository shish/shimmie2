<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArtistsConfig extends ConfigGroup
{
    public const KEY = "artists";

    #[ConfigMeta("Artists per page", ConfigType::INT, default: 20)]
    public const ARTISTS_PER_PAGE = "artistsPerPage";
}
