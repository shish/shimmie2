<?php

declare(strict_types=1);

namespace Shimmie2;

final class LinkImageConfig extends ConfigGroup
{
    public const KEY = "link_image";

    #[ConfigMeta("Text format", ConfigType::STRING, default: '$title - $id ($ext $size $filesize)')]
    public const TEXT_FORMAT = 'ext_link-img_text-link_format';
}
