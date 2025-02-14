<?php

declare(strict_types=1);

namespace Shimmie2;

class AvatarPostConfig extends ConfigGroup
{
    public ?string $title = "Avatars (Post)";

    #[ConfigMeta("Size", ConfigType::INT)]
    public const SIZE = "avatar_post_size";
}

class AvatarPostUserConfig extends ConfigGroup
{
    #[ConfigMeta("Post ID", ConfigType::INT)]
    public const AVATAR_ID = "avatar_post_id";

    #[ConfigMeta("Scale", ConfigType::INT)]
    public const AVATAR_SCALE = "avatar_post_scale";

    #[ConfigMeta("X%", ConfigType::INT)]
    public const AVATAR_X = "avatar_post_x";

    #[ConfigMeta("Y%", ConfigType::INT)]
    public const AVATAR_Y = "avatar_post_y";
}
