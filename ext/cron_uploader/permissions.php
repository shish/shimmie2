<?php

declare(strict_types=1);

namespace Shimmie2;

final class CronUploaderPermission extends PermissionGroup
{
    public const KEY = "cron_uploader";

    #[PermissionMeta("Admin")]
    public const CRON_ADMIN = "cron_admin";

    #[PermissionMeta("Run")]
    public const CRON_RUN = "cron_run";
}
