<?php

declare(strict_types=1);

namespace Shimmie2;

final class TombstonesConfig extends ConfigGroup
{
    public const KEY = "tombstones";
    public ?string $title = "Tombstones";

    #[ConfigMeta(
        "Message for deleted posts",
        ConfigType::STRING,
        input: ConfigInput::TEXTAREA,
        default: '$HASH was deleted on $DATE by $USER',
        help: 'with $HASH, $DATE, and $USER'
    )]
    public const MESSAGE = "tombstones_message";
}
