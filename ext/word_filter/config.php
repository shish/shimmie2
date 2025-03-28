<?php

declare(strict_types=1);

namespace Shimmie2;

final class WordFilterConfig extends ConfigGroup
{
    public const KEY = "word_filter";

    #[ConfigMeta("", ConfigType::STRING, input: ConfigInput::TEXTAREA, help: "Each line should be search term and replace term, separated by a comma")]
    public const FILTER = "word_filter";
}
