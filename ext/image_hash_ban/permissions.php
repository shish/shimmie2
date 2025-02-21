<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageHashBanPermission extends PermissionGroup
{
    public const KEY = "image_hash_ban";

    #[PermissionMeta("Ban post")]
    public const BAN_IMAGE = "ban_image";
}
