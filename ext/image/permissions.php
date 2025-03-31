<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImagePermission extends PermissionGroup
{
    public const KEY = "image";
    public ?string $title = "Posts";

    #[PermissionMeta("Create post")]
    public const CREATE_IMAGE = "create_image";

    #[PermissionMeta("Delete post")]
    public const DELETE_IMAGE = "delete_image";

    #[PermissionMeta("Delete own post")]
    public const DELETE_OWN_IMAGE = "delete_own_image";

    #[PermissionMeta("Edit files")]
    public const EDIT_FILES = "edit_files";
}
