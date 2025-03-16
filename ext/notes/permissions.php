<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotesPermission extends PermissionGroup
{
    public const KEY = "notes";

    #[PermissionMeta("Admin", help: "Broad control over all miscellaneous note features")]
    public const ADMIN = "notes_admin";

    #[PermissionMeta("Create", help: "Create new notes")]
    public const CREATE = "notes_create";

    #[PermissionMeta("Edit", help: "Edit existing notes")]
    public const EDIT = "notes_edit";

    #[PermissionMeta("Request", help: "Request new notes")]
    public const REQUEST = "notes_request";
}
