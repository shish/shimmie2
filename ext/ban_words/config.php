<?php

declare(strict_types=1);

namespace Shimmie2;

class BanWordsConfig extends ConfigGroup
{
    public const KEY = "ban_words";

    #[ConfigMeta(
        "Banned Phrases",
        ConfigType::STRING,
        input: "longtext",
        help: "One per line, lines that start with slashes are treated as regex",
        default: "
a href=
anal
blowjob
/buy-.*-online/
casino
cialis
doors.txt
fuck
hot video
kaboodle.com
lesbian
nexium
penis
/pokerst.*/
pornhub
porno
purchase
sex
sex tape
spinnenwerk.de
thx for all
TRAMADOL
ultram
very nice site
viagra
xanax
",
    )]
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
