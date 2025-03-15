<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkImportExportPermission extends PermissionGroup
{
    public const KEY = "bulk_import_export";

    #[PermissionMeta("Bulk import")]
    public const BULK_IMPORT = "bulk_import";

    #[PermissionMeta("Bulk export")]
    public const BULK_EXPORT = "bulk_export";
}
