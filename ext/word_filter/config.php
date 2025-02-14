<?php

declare(strict_types=1);

namespace Shimmie2;

class WordFilterConfig extends ConfigGroup
{
    #[ConfigMeta("", ConfigType::STRING, ui_type: "longtext", help: "Each line should be search term and replace term, separated by a comma")]
    public const FILTER = "word_filter";
}
