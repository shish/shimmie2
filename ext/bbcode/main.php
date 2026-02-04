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
                        LI(CODE("[spoiler]Voldemort is bad[/spoiler]")),
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
        $text = $this->extract_code($text);
        foreach ([
            "b", "i", "u", "s", "sup", "sub", "h1", "h2", "h3", "h4",
        ] as $el) {
            $text = \Safe\preg_replace("!\[$el\](.*?)\[/$el\]!s", "<$el>$1</$el>", $text);
        }
        $text = \Safe\preg_replace('!^&gt;&gt;([^\d].+)!', '<blockquote><small>$1</small></blockquote>', $text);
        $text = \Safe\preg_replace('!&gt;&gt;(\d+)(#c?\d+)?!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('post/view/$1$2').'">&gt;&gt;$1$2</a>', $text);
        $text = \Safe\preg_replace('!\[anchor=(.*?)\](.*?)\[/anchor\]!s', '<span class="anchor">$2 <a class="alink" href="#bb-$1" name="bb-$1" title="link to this anchor"> ¶ </a></span>', $text);  // add "bb-" to avoid clashing with eg #top
        $text = \Safe\preg_replace('!\[url=site://(.*?)(#c\d+)?\](.*?)\[/url\]!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('$1$2').'">$3</a>', $text);
        $text = \Safe\preg_replace('!\[url\]site://(.*?)(#c\d+)?\[/url\]!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('$1$2').'">$1$2</a>', $text);
        $text = \Safe\preg_replace('!\[url=((?:https?|ftp|irc|mailto)://.*?)\](.*?)\[/url\]!s', '<a href="$1">$2</a>', $text);
        $text = \Safe\preg_replace('!\[url\]((?:https?|ftp|irc|mailto)://.*?)\[/url\]!s', '<a href="$1">$1</a>', $text);
        $text = \Safe\preg_replace('!\[email\](.*?)\[/email\]!s', '<a href="mailto:$1">$1</a>', $text);
        $text = \Safe\preg_replace('!\[img\](https?:\/\/.*?)\[/img\]!s', '<img alt="user image" src="$1">', $text);
        $text = \Safe\preg_replace('!\[img\]site://(.*?)(#c\d+)?\[/img\]!s', '<img alt="user image" src="'.make_link('$1$2').'">', $text);
        $text = \Safe\preg_replace('!\[\[([^\|\]]+)\|([^\]]+)\]\]!s', '<a href="'.make_link('wiki/$1').'">$2</a>', $text);
        $text = \Safe\preg_replace('!\[\[([^\]]+)\]\]!s', '<a href="'.make_link('wiki/$1').'">$1</a>', $text);
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
        $text = $this->filter_spoiler($text);
        $text = $this->insert_code($text);
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

    private function filter_spoiler(string $text): string
    {
        return str_replace(
            ["[spoiler]","[/spoiler]"],
            ["<span style=\"background-color:#000; color:#000;\">","</span>"],
            $text
        );
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

    private function extract_code(string $text): string
    {
        # at the end of this function, the only code! blocks should be
        # the ones we've added -- others may contain malicious content,
        # which would only appear after decoding
        $text = str_replace("[code!]", "[code]", $text);
        $text = str_replace("[/code!]", "[/code]", $text);

        $l1 = strlen("[code]");
        $l2 = strlen("[/code]");
        while (true) {
            $start = strpos($text, "[code]");
            if ($start === false) {
                break;
            }

            $end = strpos($text, "[/code]", $start);
            if ($end === false) {
                break;
            }

            if ($end < $start) {
                break;
            }

            $beginning = substr($text, 0, $start);
            $middle = base64_encode(substr($text, $start + $l1, ($end - $start - $l1)));
            $ending = substr($text, $end + $l2, (strlen($text) - $end + $l2));

            $text = $beginning . "[code!]" . $middle . "[/code!]" . $ending;
        }
        return $text;
    }

    private function insert_code(string $text): string
    {
        $l1 = strlen("[code!]");
        $l2 = strlen("[/code!]");
        while (true) {
            $start = strpos($text, "[code!]");
            if ($start === false) {
                break;
            }

            $end = strpos($text, "[/code!]");
            if ($end === false) {
                break;
            }

            $beginning = substr($text, 0, $start);
            $middle = base64_decode(substr($text, $start + $l1, ($end - $start - $l1)), true);
            $ending = substr($text, $end + $l2, (strlen($text) - $end + $l2));

            $text = $beginning . "<pre><code>" . $middle . "</code></pre>" . $ending;
        }
        return $text;
    }
}
