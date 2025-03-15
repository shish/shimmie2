<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTitlesPermission extends PermissionGroup
{
    public const KEY = "post_titles";

    #[PermissionMeta("Edit post title")]
    public const EDIT_IMAGE_TITLE = "edit_image_title";
}
