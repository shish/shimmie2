<?php

declare(strict_types=1);

namespace Shimmie2;

class BBCode extends FormatterExtension
{
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
            $text = preg_replace("!\[$el\](.*?)\[/$el\]!s", "<$el>$1</$el>", $text);
        }
        $text = preg_replace('!^&gt;&gt;([^\d].+)!', '<blockquote><small>$1</small></blockquote>', $text);
        $text = preg_replace('!&gt;&gt;(\d+)(#c?\d+)?!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('post/view/$1$2').'">&gt;&gt;$1$2</a>', $text);
        $text = preg_replace('!\[anchor=(.*?)\](.*?)\[/anchor\]!s', '<span class="anchor">$2 <a class="alink" href="#bb-$1" name="bb-$1" title="link to this anchor"> ¶ </a></span>', $text);  // add "bb-" to avoid clashing with eg #top
        $text = preg_replace('!\[url=site://(.*?)(#c\d+)?\](.*?)\[/url\]!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('$1$2').'">$3</a>', $text);
        $text = preg_replace('!\[url\]site://(.*?)(#c\d+)?\[/url\]!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('$1$2').'">$1$2</a>', $text);
        $text = preg_replace('!\[url=((?:https?|ftp|irc|mailto)://.*?)\](.*?)\[/url\]!s', '<a href="$1">$2</a>', $text);
        $text = preg_replace('!\[url\]((?:https?|ftp|irc|mailto)://.*?)\[/url\]!s', '<a href="$1">$1</a>', $text);
        $text = preg_replace('!\[email\](.*?)\[/email\]!s', '<a href="mailto:$1">$1</a>', $text);
        $text = preg_replace('!\[img\](https?:\/\/.*?)\[/img\]!s', '<img alt="user image" src="$1">', $text);
        $text = preg_replace('!\[\[([^\|\]]+)\|([^\]]+)\]\]!s', '<a href="'.make_link('wiki/$1').'">$2</a>', $text);
        $text = preg_replace('!\[\[([^\]]+)\]\]!s', '<a href="'.make_link('wiki/$1').'">$1</a>', $text);
        $text = preg_replace("!\n\s*\n!", "\n\n", $text);
        $text = str_replace("\n", "\n<br>", $text);
        $text = preg_replace("/\[quote\](.*?)\[\/quote\]/s", "<blockquote><small>\\1</small></blockquote>", $text);
        $text = preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/s", "<blockquote><em>\\1 said:</em><br><small>\\2</small></blockquote>", $text);
        while (preg_match("/\[list\](.*?)\[\/list\]/s", $text)) {
            $text = preg_replace("/\[list\](.*?)\[\/list\]/s", "<ul>\\1</ul>", $text);
        }
        while (preg_match("/\[ul\](.*?)\[\/ul\]/s", $text)) {
            $text = preg_replace("/\[ul\](.*?)\[\/ul\]/s", "<ul>\\1</ul>", $text);
        }
        while (preg_match("/\[ol\](.*?)\[\/ol\]/s", $text)) {
            $text = preg_replace("/\[ol\](.*?)\[\/ol\]/s", "<ol>\\1</ol>", $text);
        }
        $text = preg_replace("/\[li\](.*?)\[\/li\]/s", "<li>\\1</li>", $text);
        $text = preg_replace("#\[\*\]#s", "<li>", $text);
        $text = preg_replace("#<br><(li|ul|ol|/ul|/ol)>#s", "<\\1>", $text);
        $text = preg_replace("#\[align=(left|center|right)\](.*?)\[\/align\]#s", "<div style='text-align:\\1;'>\\2</div>", $text);
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
            $text = preg_replace("!\[$el\](.*?)\[/$el\]!s", '$1', $text);
        }
        $text = preg_replace("!\[anchor=(.*?)\](.*?)\[/anchor\]!s", '$2', $text);
        $text = preg_replace("!\[url=(.*?)\](.*?)\[/url\]!s", '$2', $text);
        $text = preg_replace("!\[img\](.*?)\[/img\]!s", "", $text);
        $text = preg_replace("!\[\[([^\|\]]+)\|([^\]]+)\]\]!s", '$2', $text);
        $text = preg_replace("!\[\[([^\]]+)\]\]!s", '$1', $text);
        $text = preg_replace("!\[quote\](.*?)\[/quote\]!s", "", $text);
        $text = preg_replace("!\[quote=(.*?)\](.*?)\[/quote\]!s", "", $text);
        $text = preg_replace("!\[/?(list|ul|ol)\]!", "", $text);
        $text = preg_replace("!\[\*\](.*?)!s", '$1', $text);
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
            $middle = base64_decode(substr($text, $start + $l1, ($end - $start - $l1)));
            $ending = substr($text, $end + $l2, (strlen($text) - $end + $l2));

            $text = $beginning . "<pre class='code'>" . $middle . "</pre>" . $ending;
        }
        return $text;
    }
}
