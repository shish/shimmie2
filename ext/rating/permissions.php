<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsPermission extends PermissionGroup
{
    public const KEY = "rating";

    #[PermissionMeta("Edit")]
    public const EDIT_IMAGE_RATING = "edit_image_rating";

    #[PermissionMeta("Bulk edit")]
    public const BULK_EDIT_IMAGE_RATING = "bulk_edit_image_rating";
}
