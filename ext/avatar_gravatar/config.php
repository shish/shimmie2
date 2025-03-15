<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarGravatarConfig extends ConfigGroup
{
    public const KEY = "avatar_gravatar";
    public ?string $title = "Avatars (Gravatar)";

    #[ConfigMeta("Type", ConfigType::STRING, options: [
        'Default' => 'default',
        'Wavatar' => 'wavatar',
        'Monster ID' => 'monsterid',
        'Identicon' => 'identicon'
    ])]
    public const GRAVATAR_TYPE = "avatar_gravatar_type";

    #[ConfigMeta("Rating", ConfigType::STRING, default: 'g', options: [
        'G' => 'g',
        'PG' => 'pg',
        'R' => 'r',
        'X' => 'x'
    ])]
    public const GRAVATAR_RATING = "avatar_gravatar_rating";

    #[ConfigMeta("Default", ConfigType::STRING, default: "", advanced: true)]
    public const GRAVATAR_DEFAULT = "avatar_gravatar_default";
}
