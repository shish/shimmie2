<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiPermission extends PermissionGroup
{
    public const KEY = "wiki";

    #[PermissionMeta("Admin")]
    public const ADMIN = "wiki_admin";

    #[PermissionMeta("Edit")]
    public const EDIT_WIKI_PAGE = "edit_wiki_page";
}
