<?php

declare(strict_types=1);

namespace Shimmie2;

class TagListTheme extends Themelet
{
    public string $heading = "";
    public string $list = "";
    public ?string $navigation;
    private mixed $tagcategories = null;

    public function set_heading(string $text): void
    {
        $this->heading = $text;
    }

    public function set_tag_list(string $list): void
    {
        $this->list = $list;
    }

    public function set_navigation(string $nav): void
    {
        $this->navigation = $nav;
    }

    public function display_page(Page $page): void
    {
        $page->set_title("Tag List");
        $page->set_heading($this->heading);
        $page->add_block(new Block("Tags", $this->list));
        $page->add_block(new Block("Navigation", $this->navigation, "left", 0));
    }

    // =======================================================================

    protected function get_tag_list_preamble(): string
    {
        global $config;

        $tag_info_link_is_visible = !is_null($config->get_string(TagListConfig::INFO_LINK));
        $tag_count_is_visible = $config->get_bool("tag_list_numbers");

        return '
			<table class="tag_list">
				<colgroup>' .
                    ($tag_info_link_is_visible ? '<col class="tag_info_link_column">' : '') .
                    ('<col class="tag_name_column">') .
                    ($tag_count_is_visible ? '<col class="tag_count_column">' : '') . '
				</colgroup>
				<thead>
					<tr>' .
                        ($tag_info_link_is_visible ? '<th class="tag_info_link_cell"></th>' : '') .
                        ('<th class="tag_name_cell">Tag</th>') .
                        ($tag_count_is_visible ? '<th class="tag_count_cell">#</th>' : '') . '
					</tr>
				</thead>
				<tbody>';
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
            $this->tagcategories = new TagCategories();
            $tag_category_dict = $this->tagcategories->getKeyedDict();
        } else {
            $tag_category_dict = [];
        }
        $tag_categories_html = [];
        $tag_categories_count = [];

        foreach ($tag_infos as $row) {
            $split = self::return_tag($row, $tag_category_dict);
            $category = $split[0];
            $tag_html = $split[1];
            if (!isset($tag_categories_html[$category])) {
                $tag_categories_html[$category] = $this->get_tag_list_preamble();
            }
            $tag_categories_html[$category] .= "<tr>$tag_html</tr>";

            if (!isset($tag_categories_count[$category])) {
                $tag_categories_count[$category] = 0;
            }
            $tag_categories_count[$category] += 1;
        }

        foreach (array_keys($tag_categories_html) as $category) {
            $tag_categories_html[$category] .= '</tbody></table>';
        }

        asort($tag_categories_html);
        if (isset($tag_categories_html[' '])) {
            $main_html = $tag_categories_html[' '];
        } else {
            $main_html = null;
        }
        unset($tag_categories_html[' ']);

        foreach (array_keys($tag_categories_html) as $category) {
            if ($tag_categories_count[$category] < 2) {
                $category_display_name = html_escape($tag_category_dict[$category]['display_singular']);
            } else {
                $category_display_name = html_escape($tag_category_dict[$category]['display_multiple']);
            }
            $page->add_block(new Block($category_display_name, $tag_categories_html[$category], "left", 9));
        }

        if ($main_html !== null) {
            $page->add_block(new Block("Tags", $main_html, "left", 10));
        }
    }

    /**
     * @param array<array{tag: string, count: int}> $tag_infos
     */
    private function get_tag_list_html(array $tag_infos, string $sort): string
    {
        if ($sort == TagListConfig::SORT_ALPHABETICAL) {
            asort($tag_infos);
        }

        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
            $this->tagcategories = new TagCategories();
            $tag_category_dict = $this->tagcategories->getKeyedDict();
        } else {
            $tag_category_dict = [];
        }
        $main_html = $this->get_tag_list_preamble();

        foreach ($tag_infos as $row) {
            $split = $this->return_tag($row, $tag_category_dict);
            //$category = $split[0];
            $tag_html = $split[1];
            $main_html .= "<tr>$tag_html</tr>";
        }

        $main_html .= '</tbody></table>';

        return $main_html;
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

        $main_html = $this->get_tag_list_html(
            $tag_infos,
            $config->get_string(TagListConfig::POPULAR_SORT)
        );
        $main_html .= "&nbsp;<br><a class='more' href='".make_link("tags")."'>Full List</a>\n";

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

        $page->add_block(new Block("Refine Search", $main_html, "left", 60));
    }

    /**
     * @param array{tag: string, count: int} $row
     * @param array<string, array{color: string}> $tag_category_dict
     * @return array{0: string, 1: string}
     */
    public function return_tag(array $row, array $tag_category_dict): array
    {
        global $config;

        $display_html = '';
        $tag = $row['tag'];
        $h_tag = html_escape($tag);

        $tag_category_css = '';
        $tag_category_style = '';
        $h_tag_split = explode(':', html_escape($tag), 2);
        $category = ' ';

        // we found a tag, see if it's valid!
        if ((count($h_tag_split) > 1) and array_key_exists($h_tag_split[0], $tag_category_dict)) {
            $category = $h_tag_split[0];
            $h_tag = $h_tag_split[1];
            $tag_category_css .= ' tag_category_'.$category;
            $tag_category_style .= 'style="color:'.html_escape($tag_category_dict[$category]['color']).';" ';
        }

        $h_tag_no_underscores = str_replace("_", " ", $h_tag);
        $count = $row['count'];
        // if($n++) $display_html .= "\n<br/>";
        if (!is_null($config->get_string(TagListConfig::INFO_LINK))) {
            $link = html_escape(str_replace('$tag', url_escape($tag), $config->get_string(TagListConfig::INFO_LINK)));
            $display_html .= '<td class="tag_info_link_cell"> <a class="tag_info_link'.$tag_category_css.'" '.$tag_category_style.'href="'.$link.'">?</a></td>';
        }
        $link = $this->tag_link($row['tag']);
        $display_html .= '<td class="tag_name_cell"> <a class="tag_name'.$tag_category_css.'" '.$tag_category_style.'href="'.$link.'">'.$h_tag_no_underscores.'</a></td>';

        if ($config->get_bool("tag_list_numbers")) {
            $display_html .= "<td class='tag_count_cell'> <span class='tag_count'>$count</span></td>";
        }

        return [$category, $display_html];
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
            return "<a href='".$this->tag_link(join(' ', $tags))."' title='Remove' rel='nofollow'>R</a>";
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
            return "<a href='".$this->tag_link(join(' ', $tags))."' title='Add' rel='nofollow'>A</a>";
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
            return "<a href='".$this->tag_link(join(' ', $tags))."' title='Subtract' rel='nofollow'>S</a>";
        }
    }

    public function tag_link(string $tag): string
    {
        return search_link([$tag]);
    }
}
