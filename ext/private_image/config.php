<?php

declare(strict_types=1);

namespace Shimmie2;

class PrivateImageConfig extends ConfigGroup
{
    public const KEY = "private_image";
    public ?string $title = "Private Posts";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_private_image_version";
}

class PrivateImageUserConfig extends UserConfigGroup
{
    public const KEY = "private_image";
    public ?string $title = "Private Posts";

    #[ConfigMeta("Mark posts private by default", ConfigType::BOOL, default: false, permission: Permissions::SET_PRIVATE_IMAGE)]
    public const SET_DEFAULT = "user_private_image_set_default";

    #[ConfigMeta("View private posts by default", ConfigType::BOOL, default: true)]
    public const VIEW_DEFAULT = "user_private_image_view_default";
}
