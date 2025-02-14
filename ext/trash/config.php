<?php

declare(strict_types=1);

namespace Shimmie2;

class TrashConfig extends ConfigGroup
{
    public const KEY = "trash";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_trash_version";
}
