<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogDatabasePermission extends PermissionGroup
{
    public const KEY = "log_db";

    #[PermissionMeta("View event log")]
    public const VIEW_EVENTLOG = "view_eventlog";
}
