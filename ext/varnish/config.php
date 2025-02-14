<?php

declare(strict_types=1);

namespace Shimmie2;

class VarnishPurgerConfig extends ConfigGroup
{
    #[ConfigMeta("Varnish Host", ConfigType::STRING)]
    public const HOST = "varnish_host";

    #[ConfigMeta("Varnish Port", ConfigType::INT)]
    public const PORT = "varnish_port";

    #[ConfigMeta("Varnish Protocol", ConfigType::STRING)]
    public const PROTOCOL = "varnish_protocol";
}
