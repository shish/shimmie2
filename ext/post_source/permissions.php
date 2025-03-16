<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSourcePermission extends PermissionGroup
{
    public const KEY = "post_source";

    #[PermissionMeta("Edit post source")]
    public const EDIT_IMAGE_SOURCE = "edit_image_source";
}
