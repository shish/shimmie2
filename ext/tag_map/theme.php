<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, HR, P, SPAN, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\SUB;

class TagMapTheme extends Themelet
{
    /**
     * @param array<array{tag:tag-string,scaled:float}> $tag_data
     */
    public function display_map(int $tags_min, array $tag_data): void
    {
        $html = emptyHTML();
        if (Ctx::$config->get(TagMapConfig::PAGES)) {
            $html->appendChild($this->build_az($tags_min));
        }
        foreach ($tag_data as $row) {
            $tag = $row['tag'];
            $scale = $row['scaled'];
            $size = sprintf("%.2f", $scale < 0.5 ? 0.5 : $scale);
            $html->appendChild($this->build_tag($tag, show_underscores: false, style: "margin: 0em 1em; font-size: {$size}em"));
        }

        $page = Ctx::$page;
        $page->set_title("Tag List");
        $page->set_heading("Tag Map");
        $this->display_nav();
        $page->add_block(new Block(null, $html));
    }

    /**
     * @param array<array{tag:tag-string,count:int}> $tag_data
     */
    public function display_alphabetic(string $starts_with, int $tags_min, array $tag_data): void
    {
        $html = emptyHTML();
        if (Ctx::$config->get(TagMapConfig::PAGES)) {
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
            /** @var tag-string $tag */
            $tag = (string)$tag;
            if ($lastLetter !== mb_strtolower(substr($tag, 0, strlen($starts_with) + 1))) {
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

        $page = Ctx::$page;
        $page->set_title("Tag List");
        $page->set_heading("Alphabetic Tag List");
        $this->display_nav();
        $page->add_block(new Block(null, $html));
    }

    /**
     * @param array<array{tag:tag-string,count:int,scaled:float}> $tag_data
     */
    public function display_popularity(array $tag_data): void
    {
        $html = emptyHTML("Results grouped by log", SUB("10"), "(n)");
        $lastLog = "";
        foreach ($tag_data as $row) {
            $tag = $row['tag'];
            $count = $row['count'];
            $scaled = $row['scaled'];
            if ($lastLog !== $scaled) {
                $lastLog = $scaled;
                $html->appendChild(BR());
                $html->appendChild(BR());
                $html->appendChild("$lastLog");
                $html->appendChild(BR());
            }
            $html->appendChild(SPAN(["style" => "margin-right: 1em; white-space: nowrap;"], $this->build_tag($tag), " ($count)"));
            $html->appendChild(" ");
        }

        $page = Ctx::$page;
        $page->set_title("Tag List");
        $page->set_heading("Tag List by Popularity");
        $this->display_nav();
        $page->add_block(new Block(null, $html));
    }

    protected function display_nav(): void
    {
        Ctx::$page->add_to_navigation(joinHTML(
            BR(),
            [
                " ",
                A(["href" => make_link("tags/map")], "Map"),
                A(["href" => make_link("tags/alphabetic")], "Alphabetic"),
                A(["href" => make_link("tags/popularity")], "Popularity"),
                " ",
                A(["href" => Url::current()->withModifiedQuery(["mincount" => "1"])], "Show All"),
            ]
        ));
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
            $html->appendChild(A(["href" => Url::current()->withModifiedQuery(["starts_with" => $a])], "$a "));
        }
        return emptyHTML($html, P(), HR());
    }
}
