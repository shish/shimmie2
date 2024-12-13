<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, BR, rawHTML, emptyHTML, joinHTML, TABLE, COLGROUP, COL, THEAD, TH, TR, TD, SPAN};

class TagListTheme extends Themelet
{
    public string $heading = "";
    public string $list = "";

    public function set_heading(string $text): void
    {
        $this->heading = $text;
    }

    public function set_tag_list(string $list): void
    {
        $this->list = $list;
    }

    public function display_page(Page $page): void
    {
        $page->set_title("Tag List");
        $page->set_heading($this->heading);
        $page->add_block(new Block("Tags", rawHTML($this->list)));

        $nav = joinHTML(
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
        );

        $page->add_block(new Block("Navigation", $nav, "left", 0));
    }

    // =======================================================================

    protected function get_tag_list_preamble(): HTMLElement
    {
        global $config;

        $tag_info_link_is_visible = !empty($config->get_string(TagListConfig::INFO_LINK));
        $tag_count_is_visible = $config->get_bool(TagListConfig::SHOW_NUMBERS);

        return TABLE(
            ["class" => "tag_list"],
            COLGROUP(
                ($tag_info_link_is_visible ? COL(["class" => "tag_info_link_column"]) : ''),
                COL(["class" => "tag_name_column"]),
                ($tag_count_is_visible ? COL(["class" => "tag_count_column"]) : '')
            ),
            THEAD(
                TR(
                    ($tag_info_link_is_visible ? TH(["class" => "tag_info_link_cell"]) : ''),
                    TH(["class" => "tag_name_cell"], "Tag"),
                    ($tag_count_is_visible ? TH(["class" => "tag_count_cell"], "#") : '')
                )
            ),
        );
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    public function display_split_related_block(Page $page, array $tag_infos): void
    {
        global $config;

        if ($config->get_string(TagListConfig::RELATED_SORT) == TagListConfig::SORT_ALPHABETICAL) {
            asort($tag_infos);
        }

        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
            $tag_category_dict = TagCategories::getKeyedDict();
        } else {
            $tag_category_dict = [];
        }
        $tag_categories_html = [];
        $tag_categories_count = [];

        foreach ($tag_infos as $row) {
            $tag = $row['tag'];
            $category = TagCategories::get_tag_category($tag);
            if (!isset($tag_categories_html[$category])) {
                $tag_categories_html[$category] = $this->get_tag_list_preamble();
            }
            $tag_categories_html[$category]->appendChild(self::build_tag_row($row));

            if (!isset($tag_categories_count[$category])) {
                $tag_categories_count[$category] = 0;
            }
            $tag_categories_count[$category] += 1;
        }

        asort($tag_categories_html);
        foreach (array_keys($tag_categories_html) as $category) {
            if ($category == '') {
                $category_display_name = 'Tags';
                $prio = 10;
            } elseif ($tag_categories_count[$category] < 2) {
                $category_display_name = $tag_category_dict[$category]['display_singular'];
                $prio = 9;
            } else {
                $category_display_name = $tag_category_dict[$category]['display_multiple'];
                $prio = 9;
            }
            $page->add_block(new Block($category_display_name, $tag_categories_html[$category], "left", $prio));
        }
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    private function get_tag_list_html(array $tag_infos, string $sort): HTMLElement
    {
        if ($sort == TagListConfig::SORT_ALPHABETICAL) {
            asort($tag_infos);
        }

        $table = $this->get_tag_list_preamble();
        foreach ($tag_infos as $row) {
            $table->appendChild(self::build_tag_row($row));
        }
        return $table;
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    public function display_related_block(Page $page, array $tag_infos, string $block_name): void
    {
        global $config;

        $main_html = $this->get_tag_list_html(
            $tag_infos,
            $config->get_string(TagListConfig::RELATED_SORT)
        );

        $page->add_block(new Block($block_name, $main_html, "left", 10));
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    public function display_popular_block(Page $page, array $tag_infos): void
    {
        global $config;

        $main_html = emptyHTML(
            $this->get_tag_list_html(
                $tag_infos,
                $config->get_string(TagListConfig::POPULAR_SORT)
            ),
            rawHTML("&nbsp;"),
            BR(),
            A(["class" => "more", "href" => make_link("tags")], "Full List")
        );
        $page->add_block(new Block("Popular Tags", $main_html, "left", 60));
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     * @param string[] $search
     */
    public function display_refine_block(Page $page, array $tag_infos, array $search): void
    {
        global $config;

        $main_html = $this->get_tag_list_html(
            $tag_infos,
            $config->get_string(TagListConfig::POPULAR_SORT)
        );
        $main_html .= "&nbsp;<br><a class='more' href='".make_link("tags")."'>Full List</a>\n";

        $page->add_block(new Block("Refine Search", rawHTML($main_html), "left", 60));
    }

    /**
     * @param array{tag: string, count: int} $row
     */
    public function build_tag_row(array $row): HTMLElement
    {
        global $config;

        $tag = $row['tag'];
        $count = $row['count'];

        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
            $tag_category_dict = TagCategories::getKeyedDict();
            $tag_category = TagCategories::get_tag_category($tag);
            $tag_body = TagCategories::get_tag_body($tag);
        } else {
            $tag_category_dict = [];
            $tag_category = null;
            $tag_body = $tag;
        }

        $tr = TR();

        $info_link_template = $config->get_string(TagListConfig::INFO_LINK);
        if (!empty($info_link_template)) {
            $tr->appendChild(TD(
                ["class" => "tag_info_link_cell"],
                A(
                    [
                        "class" => "tag_info_link",
                        "href" => str_replace('$tag', url_escape($tag), $info_link_template),
                    ],
                    "?"
                )
            ));
        }

        $tr->appendChild(TD(
            ["class" => "tag_name_cell"],
            $this->build_tag($tag, show_underscores: false, show_category: false)
        ));

        if ($config->get_bool(TagListConfig::SHOW_NUMBERS)) {
            $tr->appendChild(TD(
                ["class" => "tag_count_cell"],
                SPAN(["class" => "tag_count"], $count)
            ));
        }

        return $tr;
    }

    /**
     * @param string[] $tags
     */
    protected function ars(string $tag, array $tags): string
    {
        // FIXME: a better fix would be to make sure the inputs are correct
        $tag = strtolower($tag);
        $tags = array_map("strtolower", $tags);
        $html = "";
        $html .= " <span class='ars'>(";
        $html .= $this->get_add_link($tags, $tag);
        $html .= $this->get_remove_link($tags, $tag);
        $html .= $this->get_subtract_link($tags, $tag);
        $html .= ")</span>";
        return $html;
    }

    /**
     * @param string[] $tags
     */
    protected function get_remove_link(array $tags, string $tag): string
    {
        if (!in_array($tag, $tags) && !in_array("-$tag", $tags)) {
            return "";
        } else {
            $tags = array_diff($tags, [$tag, "-$tag"]);
            return "<a href='".search_link($tags)."' title='Remove' rel='nofollow'>R</a>";
        }
    }

    /**
     * @param string[] $tags
     */
    protected function get_add_link(array $tags, string $tag): string
    {
        if (in_array($tag, $tags)) {
            return "";
        } else {
            $tags = array_diff($tags, ["-$tag"]) + [$tag];
            return "<a href='".search_link($tags)."' title='Add' rel='nofollow'>A</a>";
        }
    }

    /**
     * @param string[] $tags
     */
    protected function get_subtract_link(array $tags, string $tag): string
    {
        if (in_array("-$tag", $tags)) {
            return "";
        } else {
            $tags = array_diff($tags, [$tag]) + ["-$tag"];
            return "<a href='".search_link($tags)."' title='Subtract' rel='nofollow'>S</a>";
        }
    }
}
