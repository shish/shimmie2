<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivateImageUserConfig extends UserConfigGroup
{
    public const KEY = "private_image";
    public ?string $title = "Private Posts";

    #[ConfigMeta("Mark posts private by default", ConfigType::BOOL, default: false, permission: PrivateImagePermission::SET_PRIVATE_IMAGE)]
    public const SET_DEFAULT = "user_private_image_set_default";

    #[ConfigMeta("View private posts by default", ConfigType::BOOL, default: true)]
    public const VIEW_DEFAULT = "user_private_image_view_default";
}
