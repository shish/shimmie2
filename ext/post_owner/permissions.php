<?php

declare(strict_types=1);

namespace Shimmie2;

class PostOwnerPermission extends PermissionGroup
{
    public const KEY = "post_owner";

    #[PermissionMeta("Edit post owner")]
    public const EDIT_IMAGE_OWNER = "edit_image_owner";
}
