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

    #[PermissionMeta("Skip CAPTCHA")]
    public const SKIP_CAPTCHA = "bypass_comment_captcha";

    #[PermissionMeta("Bypass Comment Lock", help: "Allow a user to comment on posts with locked comments")]
    public const BYPASS_COMMENT_LOCK = "bypass_comment_lock";

    #[PermissionMeta("Edit Comment Lock", help: "Allow a user to lock/unlock comments on posts")]
    public const EDIT_COMMENT_LOCK = "edit_comment_lock";
}
