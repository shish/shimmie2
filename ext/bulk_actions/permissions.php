<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkActionsPermission extends PermissionGroup
{
    public const KEY = "bulk_actions";

    #[PermissionMeta("Perform bulk actions")]
    public const PERFORM_BULK_ACTIONS = "perform_bulk_actions";

    #[PermissionMeta("Bulk edit post tag")]
    public const BULK_EDIT_IMAGE_TAG = "bulk_edit_image_tag";

    #[PermissionMeta("Bulk edit post source")]
    public const BULK_EDIT_IMAGE_SOURCE = "bulk_edit_image_source";
}
