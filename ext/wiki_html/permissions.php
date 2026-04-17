<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiHtmlPermission extends PermissionGroup
{
    public const KEY = "wiki_html";

    #[PermissionMeta("Use [html] Tags")]
    public const USE_HTML = "wiki_use_html";
}
