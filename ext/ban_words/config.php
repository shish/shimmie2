<?php

declare(strict_types=1);

namespace Shimmie2;

class BanWordsConfig extends ConfigGroup
{
    public const KEY = "ban_words";

    #[ConfigMeta("Banned Phrases", ConfigType::STRING, ui_type: "longtext", help: "One per line, lines that start with slashes are treated as regex")]
    public const BANNED_WORDS = "banned_words";

    public function tweak_html(\MicroHTML\HTMLElement $html): \MicroHTML\HTMLElement
    {
        $failed = [];
        foreach (BanWords::get_words() as $word) {
            if ($word[0] == '/') {
                try {
                    \Safe\preg_match($word, "");
                } catch (\Exception $e) {
                    $failed[] = $word;
                }
            }
        }
        if ($failed) {
            $html = \MicroHTML\emptyHTML($html, "Failed regexes: ".join(", ", $failed));
        }

        return $html;
    }
}
