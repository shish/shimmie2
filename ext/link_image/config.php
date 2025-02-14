<?php

declare(strict_types=1);

namespace Shimmie2;

class LinkImageConfig extends ConfigGroup
{
    #[ConfigMeta("Text format", ConfigType::STRING)]
    public const TEXT_FORMAT = 'ext_link-img_text-link_format';
}
