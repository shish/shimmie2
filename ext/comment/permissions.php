<?php

declare(strict_types=1);

namespace Shimmie2;

final class CommentPermission extends PermissionGroup
{
    public const KEY = "comment";

    #[PermissionMeta("Create")]
    public const CREATE_COMMENT = "create_comment";

    #[PermissionMeta("Delete")]
    public const DELETE_COMMENT = "delete_comment";

    #[PermissionMeta("Bypass Checks", help: "Allow a user to make comments even if the spam-detector disapproves")]
    public const BYPASS_COMMENT_CHECKS = "bypass_comment_checks";

    #[PermissionMeta("Skip CAPTCHA")]
    public const SKIP_CAPTCHA = "bypass_comment_captcha";
}
