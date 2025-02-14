<?php

declare(strict_types=1);

namespace Shimmie2;

class IPBanConfig extends ConfigGroup
{
    public const KEY = "ipban";
    public ?string $title = "IP Ban";

    #[ConfigMeta("Message for banned users", ConfigType::STRING, ui_type: "longtext", help: 'with $IP, $DATE, $ADMIN, $REASON, and $CONTACT')]
    public const MESSAGE = "ipban_message";

    #[ConfigMeta("Message for ghost users", ConfigType::STRING, ui_type: "longtext", advanced: true)]
    public const MESSAGE_GHOST = "ipban_message_ghost";

    #[ConfigMeta("Message for ghost anons", ConfigType::STRING, ui_type: "longtext", advanced: true)]
    public const MESSAGE_ANON_GHOST = "ipban_message_anon-ghost";
}
