<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, BR, emptyHTML, rawHTML, joinHTML, P, HR, SPAN};

class TagMapTheme extends Themelet
{
    /**
     * @param array<array{tag:string,scaled:float}> $tag_data
     */
    public function display_map(int $tags_min, array $tag_data): void
    {
        global $config, $page;

        $html = emptyHTML();
        if ($config->get_bool(TagMapConfig::PAGES)) {
            $html->appendChild($this->build_az($tags_min));
        }
        foreach ($tag_data as $row) {
            $tag = $row['tag'];
            $scale = (float)$row['scaled'];
            $size = sprintf("%.2f", $scale < 0.5 ? 0.5 : $scale);
            $html->appendChild(rawHTML("&nbsp;"));
            $html->appendChild($this->build_tag($tag, show_underscores: false, style: "font-size: {$size}em"));
            $html->appendChild(rawHTML("&nbsp;"));
        }

        $page->set_title("Tag List");
        $page->set_heading("Tag Map");
        $this->display_nav();
        $page->add_block(new Block(null, $html));
    }

    /**
     * @param array<array{tag:string,count:int}> $tag_data
     */
    public function display_alphabetic(string $starts_with, int $tags_min, array $tag_data): void
    {
        global $config, $page;

        $html = emptyHTML();
        if ($config->get_bool(TagMapConfig::PAGES)) {
            $html->appendChild($this->build_az($tags_min));
        }

        /*
          strtolower() vs. mb_strtolower()
          ( See https://www.php.net/manual/en/function.mb-strtolower.php for more info )

          PHP5's strtolower function does not support Unicode (UTF-8) properly, so
          you have to use another function, mb_strtolower, to handle UTF-8 strings.

          What's worse is that mb_strtolower is horribly SLOW.

          It would probably be better to have a config option for the Tag List that
          would allow you to specify if there are UTF-8 tags.

        */
        mb_internal_encoding('UTF-8');

        $lastLetter = "";
        # postres utf8 string sort ignores punctuation, so we get "aza, a-zb, azc"
        # which breaks down into "az, a-, az" :(
        ksort($tag_data, SORT_STRING | SORT_FLAG_CASE);
        $n = 0;
        foreach ($tag_data as $tag => $count) {
            // In PHP, $array["10"] sets the array key as int(10), not string("10")...
            $tag = (string)$tag;
            if ($lastLetter != mb_strtolower(substr($tag, 0, strlen($starts_with) + 1))) {
                $lastLetter = mb_strtolower(substr($tag, 0, strlen($starts_with) + 1));
                if ($n++ > 0) {
                    $html->appendChild(BR());
                    $html->appendChild(BR());
                }
                $html->appendChild($lastLetter);
                $html->appendChild(BR());
            }
            $html->appendChild($this->build_tag($tag));
        }

        $page->set_title("Tag List");
        $page->set_heading("Alphabetic Tag List");
        $this->display_nav();
        $page->add_block(new Block(null, $html));
    }

    /**
     * @param array<array{tag:string,count:int,scaled:float}> $tag_data
     */
    public function display_popularity(array $tag_data): void
    {
        global $page;

        $html = emptyHTML(rawHTML("Results grouped by log<sub>10</sub>(n)"));
        $lastLog = "";
        foreach ($tag_data as $row) {
            $tag = $row['tag'];
            $count = $row['count'];
            $scaled = $row['scaled'];
            if ($lastLog != $scaled) {
                $lastLog = $scaled;
                $html->appendChild(BR());
                $html->appendChild(BR());
                $html->appendChild("$lastLog");
                $html->appendChild(BR());
            }
            $html->appendChild($this->build_tag($tag));
            $html->appendChild(rawHTML("&nbsp;($count)&nbsp;&nbsp;"));
        }

        $page->set_title("Tag List");
        $page->set_heading("Tag List by Popularity");
        $this->display_nav();
        $page->add_block(new Block(null, $html));
    }

    protected function display_nav(): void
    {
        global $page;
        $page->add_block(new Block("Navigation", joinHTML(
            BR(),
            [
                A(["href" => make_link()], "Index"),
                rawHTML("&nbsp;"),
                A(["href" => make_link("tags/map")], "Map"),
                A(["href" => make_link("tags/alphabetic")], "Alphabetic"),
                A(["href" => make_link("tags/popularity")], "Popularity"),
                rawHTML("&nbsp;"),
                A(["href" => modify_current_url(["mincount" => 1])], "Show All"),
            ]
        ), "left", 0));
    }

    protected function build_az(int $tags_min): HTMLElement
    {
        global $database;

        $tag_data = $database->get_col("
			SELECT DISTINCT
				LOWER(substr(tag, 1, 1))
			FROM tags
			WHERE count >= :tags_min
			ORDER BY LOWER(substr(tag, 1, 1))
		", ["tags_min" => $tags_min]);

        $html = SPAN(["class" => "atoz"]);
        foreach ($tag_data as $a) {
            $html->appendChild(A(["href" => modify_current_url(["starts_with" => $a])], "$a "));
        }
        return emptyHTML($html, P(), HR());
    }
}
