<?php

declare(strict_types=1);

namespace Shimmie2;

class ApprovalConfig extends ConfigGroup
{
    public const KEY = "approval";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_approval_version";
}
