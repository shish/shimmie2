<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivateImagePermission extends PermissionGroup
{
    public const KEY = "private_image";

    #[PermissionMeta("Set post privacy")]
    public const SET_PRIVATE_IMAGE = "set_private_image";

    #[PermissionMeta("View other people's private posts")]
    public const SET_OTHERS_PRIVATE_IMAGES = "set_others_private_images";
}
