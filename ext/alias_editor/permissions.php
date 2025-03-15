<?php

declare(strict_types=1);

namespace Shimmie2;

final class AliasEditorPermission extends PermissionGroup
{
    public const KEY = "alias_editor";

    #[PermissionMeta("Admin")]
    public const MANAGE_ALIAS_LIST = "manage_alias_list";
}
