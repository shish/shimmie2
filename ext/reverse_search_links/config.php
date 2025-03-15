<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReverseSearchLinksConfig extends ConfigGroup
{
    public const KEY = "reverse_search_links";

    #[ConfigMeta("Enabled services", ConfigType::ARRAY, options: [
        'SauceNAO' => 'SauceNAO',
        'TinEye' => 'TinEye',
        'trace.moe' => 'trace.moe',
        'ascii2d' => 'ascii2d',
        'Yandex' => 'Yandex',
    ], default: ['SauceNAO', 'TinEye', 'trace.moe', 'ascii2d', 'Yandex'], advanced: true)]
    public const ENABLED_SERVICES = "ext_reverse_search_links_enabled_services";
}
