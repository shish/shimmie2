<?php

declare(strict_types=1);

namespace Shimmie2;

final class ApprovalPermission extends PermissionGroup
{
    public const KEY = "approval";

    #[PermissionMeta("Approve Posts")]
    public const APPROVE_IMAGE = "approve_image";

    #[PermissionMeta("Bypass Post Approval")]
    public const BYPASS_IMAGE_APPROVAL = "bypass_image_approval";
}
