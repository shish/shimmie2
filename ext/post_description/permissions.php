<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostDescriptionPermission extends PermissionGroup
{
    public const KEY = "post_description";

    #[PermissionMeta("Edit post descriptions")]
    public const EDIT_IMAGE_DESCRIPTIONS = "edit_image_descriptions";
}
