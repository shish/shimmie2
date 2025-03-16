<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostLockPermission extends PermissionGroup
{
    public const KEY = "post_lock";

    #[PermissionMeta("Edit post lock")]
    public const EDIT_IMAGE_LOCK = "edit_image_lock";
}
