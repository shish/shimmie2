<?php

declare(strict_types=1);

namespace Shimmie2;

final class NumericScorePermission extends PermissionGroup
{
    public const KEY = "numeric_score";

    #[PermissionMeta("Vote")]
    public const CREATE_VOTE = "create_vote";

    #[PermissionMeta("Edit other people's votes")]
    public const EDIT_OTHER_VOTE = "edit_other_vote";
}
