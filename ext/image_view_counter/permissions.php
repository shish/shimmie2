<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageViewCounterPermission extends PermissionGroup
{
    public const KEY = "image_view_counter";

    #[PermissionMeta("See image view counts")]
    public const SEE_IMAGE_VIEW_COUNTS = "see_image_view_counts";
}
