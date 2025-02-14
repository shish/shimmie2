<?php

declare(strict_types=1);

namespace Shimmie2;

class PrivateImageConfig extends ConfigGroup
{
    public ?string $title = "Private Posts";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_private_image_version";
}

class PrivateImageUserConfig extends ConfigGroup
{
    public ?string $title = "Private Posts";

    #[ConfigMeta("Mark posts private by default", ConfigType::BOOL, permission: Permissions::SET_PRIVATE_IMAGE)]
    public const SET_DEFAULT = "user_private_image_set_default";

    #[ConfigMeta("View private posts by default", ConfigType::BOOL)]
    public const VIEW_DEFAULT = "user_private_image_view_default";
}
