<?php

declare(strict_types=1);

namespace Shimmie2;

class WordFilterConfig extends ConfigGroup
{
    public const KEY = "word_filter";

    #[ConfigMeta("", ConfigType::STRING, ui_type: "longtext", help: "Each line should be search term and replace term, separated by a comma")]
    public const FILTER = "word_filter";
}
