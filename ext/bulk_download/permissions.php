<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkDownloadPermission extends PermissionGroup
{
    public const KEY = "bulk_download";

    #[PermissionMeta("Bulk download")]
    public const BULK_DOWNLOAD = "bulk_download";

    #[PermissionMeta("Unlimited download size")]
    public const UNLIMITED_SIZE = "bulk_download_unlimited_size";
}
