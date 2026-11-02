<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, I, S, SUB, SUP, U};
use function MicroHTML\{BR, LI, UL, emptyHTML};
use function MicroHTML\CODE;

final class BBCode extends FormatterExtension
{
    public const KEY = "bbcode";

    #[EventListener]
    public function onHelpPageListBuilding(HelpPageListBuildingEvent $event): void
    {
        $event->add_page("formatting", "Formatting");
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === "formatting") {
            $event->add_section(
                "BBCode",
                emptyHTML(
                    "Basic Formatting tags:",
                    UL(
                        LI(CODE("[b]", B("bold"), "[/b]")),
                        LI(CODE("[i]", I("italic"), "[/i]")),
                        LI(CODE("[u]", U("underline"), "[/u]")),
                        LI(CODE("[s]", S("strikethrough"), "[/s]")),
                        LI(CODE("[sup]", SUP("superscript"), "[/sup]")),
                        LI(CODE("[sub]", SUB("subscript"), "[/sub]")),
                        LI(CODE("[h1]Heading 1[/h1]")),
                        LI(CODE("[h2]Heading 2[/h2]")),
                        LI(CODE("[h3]Heading 3[/h3]")),
                        LI(CODE("[h4]Heading 4[/h4]")),
                        LI(CODE("[align=left|center|right]Aligned Text[/align]")),
                    ),
                    BR(),
                    "Link tags:",
                    UL(
                        LI(CODE("[img]url[/img]")),
                        LI(CODE("[img]site://_images/image.jpg[/img]")),
                        LI(CODE("[url]site://help/formatting[/url]")),
                        LI(CODE("[url=site://help/formatting]Link to BBCode docs[/url]")),
                        LI(CODE("[email]webmaster@shishnet.org[/email]")),
                        LI(CODE("[[wiki article]]")),
                        LI(CODE("[[wiki article|with some text]]")),
                        LI(CODE(">>123 (link to post #123)")),
                        LI(CODE("[anchor=target]Scroll to #bb-target[/anchor]")),
                    ),
                    BR(),
                    "More format tags:",
                    UL(
                        LI(CODE("[list]...[/list] or [ul]...[/ul] (unordered list)")),
                        LI(CODE("[ol]...[/ol] (ordered list)")),
                        LI(CODE("[li]List Item[/li] or [*]List Item")),
                        LI(CODE("[code]print(\"Hello World!\");[/code]")),
                        LI(CODE("[spoiler]Aeris dies[/spoiler]")),
                        LI(CODE("[quote]To be or not to be...[/quote]")),
                        LI(CODE("[quote=Shakespeare]... That is the question[/quote]")),
                    )
                ),
            );
        }
    }

    public function format(string $text): string
    {
        $text = $this->_format($text);
        return "<span class='bbcode'>$text</span>";
    }

    public function _format(string $text): string
    {
        // Fragments of finished HTML are moved out of the text and replaced
        // with "\x00N\x00" placeholders, so that later rules can't mangle
        // them - see extract_code() and extract_links()
        $text = str_replace("\x00", "", $text);
        $stash = [];
        $put = function (string $html) use (&$stash): string {
            $stash[] = $html;
            return "\x00" . (count($stash) - 1) . "\x00";
        };
        $text = $this->extract_code($text, $put);
        $text = $this->extract_links($text, $put);
        foreach ([
            "b", "i", "u", "s", "sup", "sub", "h1", "h2", "h3", "h4",
        ] as $el) {
            $text = \Safe\preg_replace("!\[$el\](.*?)\[/$el\]!s", "<$el>$1</$el>", $text);
        }

        $text = \Safe\preg_replace("!\n\s*\n!", "\n\n", $text);
        $text = str_replace("\n", "\n<br>", $text);
        $text = \Safe\preg_replace("/\[quote\](.*?)\[\/quote\]/s", "<blockquote><small>\\1</small></blockquote>", $text);
        $text = \Safe\preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/s", "<blockquote><em>\\1 said:</em><br><small>\\2</small></blockquote>", $text);
        while (\Safe\preg_match("/\[list\](.*?)\[\/list\]/s", $text)) {
            $text = \Safe\preg_replace("/\[list\](.*?)\[\/list\]/s", "<ul>\\1</ul>", $text);
        }
        while (\Safe\preg_match("/\[ul\](.*?)\[\/ul\]/s", $text)) {
            $text = \Safe\preg_replace("/\[ul\](.*?)\[\/ul\]/s", "<ul>\\1</ul>", $text);
        }
        while (\Safe\preg_match("/\[ol\](.*?)\[\/ol\]/s", $text)) {
            $text = \Safe\preg_replace("/\[ol\](.*?)\[\/ol\]/s", "<ol>\\1</ol>", $text);
        }
        $text = \Safe\preg_replace("/\[li\](.*?)\[\/li\]/s", "<li>\\1</li>", $text);
        $text = \Safe\preg_replace("#\[\*\]#s", "<li>", $text);
        $text = \Safe\preg_replace("#<br><(li|ul|ol|/ul|/ol)>#s", "<\\1>", $text);
        $text = \Safe\preg_replace("#\[align=(left|center|right)\](.*?)\[\/align\]#s", "<div style='text-align:\\1;'>\\2</div>", $text);
        $text = \Safe\preg_replace('!^&gt;&gt;([^\d].+)!', '<blockquote><small>$1</small></blockquote>', $text);
        while (\Safe\preg_match("/\[spoiler\](.*?)\[\/spoiler\]/s", $text)) {
            $text = \Safe\preg_replace("/\[spoiler\](.*?)\[\/spoiler\]/s", "<span style=\"background-color:#000; color:#000;\">\\1</span>", $text);
        }
        $text = \Safe\preg_replace_callback(
            '!\x00(\d+)\x00!',
            fn ($m) => $stash[(int)$m[1]],
            $text
        );
        return $text;
    }

    /**
     * Render every tag which puts user input into an HTML attribute, and
     * swap the rendered HTML out for an opaque placeholder.
     *
     * Without this, later rules can capture HTML generated by earlier rules
     * into an attribute, where the generated quotes break out of the
     * attribute and let user text become new attributes (eg onerror=...).
     * Attribute captures exclude both "[" (unprocessed tags) and "\x00"
     * (placeholders), so an attribute can only ever contain user text,
     * which was HTML-escaped before formatting.
     *
     * Link text (eg the "bar" in [url=foo]bar[/url]) is left in the stream
     * so that it still gets the rest of the formatting applied.
     *
     * @param callable(string): string $put
     */
    private function extract_links(string $text, callable $put): string
    {
        $site = function (string $page, string $frag): string {
            $page = ltrim(html_entity_decode($page, ENT_QUOTES, "UTF-8"), "/");
            /** @var ?fragment-string $fragment */
            $fragment = $frag === "" ? null : substr($frag, 1);
            $url = make_link($page, fragment: $fragment);
            return htmlspecialchars((string)$url, ENT_QUOTES, "UTF-8");
        };

        // add "bb-" to avoid clashing with eg #top
        $text = \Safe\preg_replace_callback(
            '!\[anchor=([^\[\x00]*?)\](.*?)\[/anchor\]!s',
            fn ($m) => $put('<span class="anchor">')
                . $m[2]
                . $put(' <a class="alink" href="#bb-'.$m[1].'" name="bb-'.$m[1].'" title="link to this anchor"> ¶ </a></span>'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[url=site://([^\[\x00]*?)(#c\d+)?\](.*?)\[/url\]!s',
            fn ($m) => $put('<a class="shm-clink" data-clink-sel="'.$m[2].'" href="'.$site($m[1], $m[2]).'">')
                . $m[3]
                . $put('</a>'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[url\]site://([^\[\x00]*?)(#c\d+)?\[/url\]!s',
            fn ($m) => $put('<a class="shm-clink" data-clink-sel="'.($m[2] ?? "").'" href="'.$site($m[1], $m[2] ?? "").'">'.$m[1].($m[2] ?? "").'</a>'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[url=((?:https?|ftp|irc|mailto)://[^\[\x00]*?)\](.*?)\[/url\]!s',
            fn ($m) => $put('<a href="'.$m[1].'">') . $m[2] . $put('</a>'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[url\]((?:https?|ftp|irc|mailto)://[^\[\x00]*?)\[/url\]!s',
            fn ($m) => $put('<a href="'.$m[1].'">'.$m[1].'</a>'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[email\]([^\[\x00]*?)\[/email\]!s',
            fn ($m) => $put('<a href="mailto:'.$m[1].'">'.$m[1].'</a>'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[img\](https?://[^\[\x00]*?)\[/img\]!s',
            fn ($m) => $put('<img alt="user image" src="'.$m[1].'">'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[img\]site://([^\[\x00]*?)(#c\d+)?\[/img\]!s',
            fn ($m) => $put('<img alt="user image" src="'.$site($m[1], $m[2] ?? "").'">'),
            $text
        );
        $text = \Safe\preg_replace_callback(
            '!\[\[([^\|\]\x00]+)(?:\|([^\]]+))?\]\]!s',
            function ($m) use ($put) {
                $page = url_escape(html_entity_decode($m[1], ENT_QUOTES, "UTF-8"));
                $href = htmlspecialchars((string)make_link('wiki/' . $page), ENT_QUOTES, "UTF-8");
                return $put('<a href="' . $href . '">') . ($m[2] ?? $m[1]) . $put('</a>');
            },
            $text
        );
        // last, so that eg [url=https://a/&gt;&gt;1] is left as part of the url
        $text = \Safe\preg_replace_callback(
            '!&gt;&gt;(\d+)(#c?\d+)?!s',
            function ($m) use ($put) {
                $frag = $m[2] ?? "";
                /** @var ?fragment-string $fragment */
                $fragment = $frag === "" ? null : substr($frag, 1);
                $href = htmlspecialchars((string)make_link('post/view/' . $m[1], fragment: $fragment), ENT_QUOTES, "UTF-8");
                return $put('<a class="shm-clink" data-clink-sel="'.$frag.'" href="'.$href.'">&gt;&gt;'.$m[1].$frag.'</a>');
            },
            $text
        );
        return $text;
    }

    public function strip(string $text): string
    {
        foreach ([
            "b", "i", "u", "s", "sup", "sub", "h1", "h2", "h3", "h4",
            "code", "url", "email", "li",
        ] as $el) {
            $text = \Safe\preg_replace("!\[$el\](.*?)\[/$el\]!s", '$1', $text);
        }
        $text = \Safe\preg_replace("!\[anchor=(.*?)\](.*?)\[/anchor\]!s", '$2', $text);
        $text = \Safe\preg_replace("!\[url=(.*?)\](.*?)\[/url\]!s", '$2', $text);
        $text = \Safe\preg_replace("!\[img\](.*?)\[/img\]!s", "", $text);
        $text = \Safe\preg_replace("!\[\[([^\|\]]+)\|([^\]]+)\]\]!s", '$2', $text);
        $text = \Safe\preg_replace("!\[\[([^\]]+)\]\]!s", '$1', $text);
        $text = \Safe\preg_replace("!\[quote\](.*?)\[/quote\]!s", "", $text);
        $text = \Safe\preg_replace("!\[quote=(.*?)\](.*?)\[/quote\]!s", "", $text);
        $text = \Safe\preg_replace("!\[/?(list|ul|ol)\]!", "", $text);
        $text = \Safe\preg_replace("!\[\*\](.*?)!s", '$1', $text);
        $text = $this->strip_spoiler($text);
        return $text;
    }


    private function strip_spoiler(string $text): string
    {
        $l1 = strlen("[spoiler]");
        $l2 = strlen("[/spoiler]");
        while (true) {
            $start = strpos($text, "[spoiler]");
            if ($start === false) {
                break;
            }

            $end = strpos($text, "[/spoiler]");
            if ($end === false) {
                break;
            }

            if ($end < $start) {
                break;
            }

            $beginning = substr($text, 0, $start);
            $middle = str_rot13(substr($text, $start + $l1, ($end - $start - $l1)));
            $ending = substr($text, $end + $l2, (strlen($text) - $end + $l2));

            $text = $beginning . $middle . $ending;
        }
        return $text;
    }

    /**
     * Code blocks are rendered first and stashed, so that no other rules
     * (bbcode tags, newline-to-br, etc) get applied to their contents.
     *
     * @param callable(string): string $put
     */
    private function extract_code(string $text, callable $put): string
    {
        return \Safe\preg_replace_callback(
            '!\[code\](.*?)\[/code\]!s',
            fn ($m) => $put('<pre><code>' . $m[1] . '</code></pre>'),
            $text
        );
    }
}
