<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermsPermission extends PermissionGroup
{
    public const KEY = "terms";

    #[PermissionMeta("Skip T&C Gate")]
    public const SKIP_TERMS = "bypass_terms";
}
