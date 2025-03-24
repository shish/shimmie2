<?php

declare(strict_types=1);

namespace Shimmie2;

final class TaggerXMLConfig extends ConfigGroup
{
    public const KEY = "tagger_xml";

    #[ConfigMeta("Tag Max", ConfigType::INT, default: 30, advanced: true)]
    public const TAG_MAX = "ext_tagger_tag_max";

    #[ConfigMeta("Limit", ConfigType::INT, default: 30, advanced: true)]
    public const LIMIT = "ext_tagger_limit";

    #[ConfigMeta("Show Hidden", ConfigType::BOOL, default: false, advanced: true)]
    public const SHOW_HIDDEN = "ext-tagger_show-hidden";
}
