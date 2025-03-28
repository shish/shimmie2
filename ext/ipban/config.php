<?php

declare(strict_types=1);

namespace Shimmie2;

final class IPBanConfig extends ConfigGroup
{
    public const KEY = "ipban";
    public ?string $title = "IP Ban";

    #[ConfigMeta(
        "Message for banned users",
        ConfigType::STRING,
        input: ConfigInput::TEXTAREA,
        default: '<p>IP <b>$IP</b> has been banned until <b>$DATE</b> by <b>$ADMIN</b> because of <b>$REASON</b>
<p>If you couldn\'t possibly be guilty of what you\'re banned for, the person we banned probably had a dynamic IP address and so do you.
<p>See <a href="http://whatismyipaddress.com/dynamic-static">http://whatismyipaddress.com/dynamic-static</a> for more information.
<p>$CONTACT',
        help: 'with $IP, $DATE, $ADMIN, $REASON, and $CONTACT'
    )]
    public const MESSAGE = "ipban_message";

    #[ConfigMeta("Message for ghost users", ConfigType::STRING, input: ConfigInput::TEXTAREA, advanced: true)]
    public const MESSAGE_GHOST = "ipban_message_ghost";

    #[ConfigMeta("Message for ghost anons", ConfigType::STRING, input: ConfigInput::TEXTAREA, advanced: true)]
    public const MESSAGE_ANON_GHOST = "ipban_message_anon-ghost";
}
