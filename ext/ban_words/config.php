<?php

declare(strict_types=1);

namespace Shimmie2;

class BanWordsConfig extends ConfigGroup
{
    #[ConfigMeta("Banned Phrases", ConfigType::STRING, ui_type: "longtext", help: "One per line, lines that start with slashes are treated as regex")]
    public const BANNED_WORDS = "banned_words";
}
