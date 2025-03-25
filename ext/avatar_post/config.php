<?php

declare(strict_types=1);

namespace Shimmie2;

final class AvatarPostUserConfig extends UserConfigGroup
{
    public const KEY = "avatar_post";

    #[ConfigMeta("Post ID", ConfigType::INT, advanced: true)]
    public const AVATAR_ID = "avatar_post_id";

    #[ConfigMeta("Scale", ConfigType::INT, default: 100, advanced: true)]
    public const AVATAR_SCALE = "avatar_post_scale";

    #[ConfigMeta("X%", ConfigType::INT, default: 0, advanced: true)]
    public const AVATAR_X = "avatar_post_x";

    #[ConfigMeta("Y%", ConfigType::INT, default: 0, advanced: true)]
    public const AVATAR_Y = "avatar_post_y";
}
