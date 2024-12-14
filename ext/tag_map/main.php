<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{rawHTML, emptyHTML, BR, SPAN, A, P, HR};

require_once "config.php";

class TagMap extends Extension
{
    /** @var TagMapTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(TagMapConfig::TAGS_MIN, 3);
        $config->set_default_bool(TagMapConfig::PAGES, false);
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        if ($event->page_matches("tags/{sub}", method: "GET")) {
            $sub = $event->get_arg('sub');

            if ($event->get_GET('starts_with')) {
                $starts_with = $event->get_GET('starts_with') . "%";
            } else {
                if ($config->get_bool(TagMapConfig::PAGES)) {
                    $starts_with = "a%";
                } else {
                    $starts_with = "%";
                }
            }

            if ($event->get_GET('mincount')) {
                $tags_min = int_escape($event->get_GET('mincount'));
            } else {
                global $config;
                $tags_min = $config->get_int(TagMapConfig::TAGS_MIN);	// get the default.
            }

            switch ($sub) {
                case 'map':
                    $this->theme->display_page("Tag Map", $this->build_tag_map($starts_with, $tags_min));
                    break;
                case 'alphabetic':
                    $this->theme->display_page("Alphabetic Tag List", $this->build_tag_alphabetic($starts_with, $tags_min));
                    break;
                case 'popularity':
                    $this->theme->display_page("Tag List by Popularity", $this->build_tag_popularity($tags_min));
                    break;
                default:
                    // don't display anything
                    break;
            }
        } elseif ($event->page_matches("tags")) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tags/map"));
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("tags", new Link('tags/map'), "Tags");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "tags") {
            $event->add_nav_link("tags_map", new Link('tags/map'), "Map");
            $event->add_nav_link("tags_alphabetic", new Link('tags/alphabetic'), "Alphabetic");
            $event->add_nav_link("tags_popularity", new Link('tags/popularity'), "Popularity");
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Tag Map Options");
        $sb->add_int_option(TagMapConfig::TAGS_MIN, "Only show tags used at least ");
        $sb->add_label(" times");
        $sb->add_bool_option(TagMapConfig::PAGES, "<br>Paged tag lists: ");
    }

    private function build_az(int $tags_min): HTMLElement
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
            $html->appendChild(A(["href" => modify_current_url(["starts_with" => $a])], $a));
        }
        return emptyHTML($html, P(), HR());
    }

    private function build_tag_map(string $starts_with, int $tags_min): HTMLElement
    {
        global $config, $database;

        $tag_data = $database->get_all("
            SELECT
                tag,
                FLOOR(LN(LN(count - :tags_min + 1)+1)*1.5*100)/100 AS scaled
            FROM tags
            WHERE count >= :tags_min
            AND LOWER(tag) LIKE LOWER(:starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min" => $tags_min, "starts_with" => $starts_with]);

        $html = emptyHTML();
        if ($config->get_bool(TagMapConfig::PAGES)) {
            $html->appendChild($this->build_az($tags_min));
        }
        foreach ($tag_data as $row) {
            $tag = $row['tag'];
            $scale = (float)$row['scaled'];
            $size = sprintf("%.2f", $scale < 0.5 ? 0.5 : $scale);
            $html->appendChild(rawHTML("&nbsp;"));
            $html->appendChild($this->theme->build_tag($tag, style: "font-size: {$size}em"));
            $html->appendChild(rawHTML("&nbsp;"));
        }

        return $html;
    }

    private function build_tag_alphabetic(string $starts_with, int $tags_min): HTMLElement
    {
        global $config, $database;

        $tag_data = $database->get_pairs("
            SELECT tag, count
            FROM tags
            WHERE count >= :tags_min
            AND LOWER(tag) LIKE LOWER(:starts_with)
            ORDER BY LOWER(tag)
        ", ["tags_min" => $tags_min, "starts_with" => $starts_with]);

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
            $html->appendChild($this->theme->build_tag($tag));
        }

        return $html;
    }

    private function build_tag_popularity(int $tags_min): HTMLElement
    {
        global $config, $database;

        // Make sure that the value of $tags_min is at least 1.
        // Otherwise the database will complain if you try to do: LOG(0)
        if ($tags_min < 1) {
            $tags_min = 1;
        }

        $tag_data = $database->get_all("
            SELECT tag, count, FLOOR(LOG(10, count)) AS scaled
            FROM tags
            WHERE count >= :tags_min
            ORDER BY count DESC, tag ASC
        ", ["tags_min" => $tags_min]);

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
            $html->appendChild($this->theme->build_tag($tag));
            $html->appendChild(rawHTML("&nbsp;($count)&nbsp;&nbsp;"));
        }

        return $html;
    }
}
