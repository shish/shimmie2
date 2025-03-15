<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReportImagePermission extends PermissionGroup
{
    public const KEY = "report_image";

    #[PermissionMeta("Report posts")]
    public const CREATE_IMAGE_REPORT = "create_image_report";

    #[PermissionMeta("Deal with reported posts")]
    public const VIEW_IMAGE_REPORT = "view_image_report";
}
