<?php

declare(strict_types=1);

namespace Shimmie2;

final class VarnishPurgerConfig extends ConfigGroup
{
    public const KEY = "varnish";

    #[ConfigMeta("Varnish Host", ConfigType::STRING, default: '127.0.0.1')]
    public const HOST = "varnish_host";

    #[ConfigMeta("Varnish Port", ConfigType::INT, default: 80)]
    public const PORT = "varnish_port";

    #[ConfigMeta("Varnish Protocol", ConfigType::STRING, default: 'http')]
    public const PROTOCOL = "varnish_protocol";
}
