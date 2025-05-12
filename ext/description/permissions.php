<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageDescriptionPermission extends PermissionGroup
{
    public const KEY = "image_description";

    #[PermissionMeta("Edit post descriptions")]
    public const EDIT_IMAGE_DESCRIPTIONS = "edit_image_descriptions";
}