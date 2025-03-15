<?php

declare(strict_types=1);

namespace Shimmie2;

final class RSSImagesConfig extends ConfigGroup
{
    public const KEY = "rss_images";

    #[ConfigMeta(
        "Limit number of result pages",
        ConfigType::BOOL,
        default: false,
        advanced: true,
        help: "RSS is limited to 10 pages for the image list."
    )]
    public const RSS_LIMIT = "speed_hax_rss_limit";
}
