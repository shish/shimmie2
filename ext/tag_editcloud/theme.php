<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, DIV, SPAN, emptyHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\joinHTML;

class TagEditCloudTheme extends Themelet
{
    /**
     * @param array<array{tag: string, scaled: float, count: int}> $tag_data
     * @param array<tag-string> $post_tags
     */
    public function build_tag_map(array $tag_data, array $post_tags): HTMLElement
    {
        global $database;

        $used_first = Ctx::$config->get(TagEditCloudConfig::USED_FIRST);
        $def_count = Ctx::$config->get(TagEditCloudConfig::DEF_COUNT);
        $sort_method = Ctx::$config->get(TagEditCloudConfig::SORT);

        $cat_color = [];
        if (TagCategoriesInfo::is_enabled()) {
            $categories = $database->get_all("SELECT category, color FROM image_tag_categories");
            foreach ($categories as $row) {
                $cat_color[$row['category']] = $row['color'];
            }
        }

        $cloud = [];
        $precloud = [];
        $postcloud = [];

        $counter = 1;
        foreach ($tag_data as $row) {
            $full_tag = $row['tag'];

            if (TagCategoriesInfo::is_enabled()) {
                $tc = explode(':', $row['tag']);
                if (isset($tc[1]) && isset($cat_color[$tc[0]])) {
                    $h_tag = $tc[1];
                    $color = '; color:'.$cat_color[$tc[0]];
                } else {
                    $h_tag = $row['tag'];
                    $color = '';
                }
            } else {
                $h_tag = $row['tag'];
                $color = '';
            }

            $size = sprintf("%.2f", max($row['scaled'], 0.5));
            $js = 'tageditcloud_toggle_tag(this,'.\Safe\json_encode($full_tag).')';

            if (in_array($row['tag'], $post_tags)) {
                $entry = SPAN([
                    'onclick' => $js,
                    'class' => 'tag-selected',
                    'style' => "font-size: {$size}em$color",
                    'title' => $row['count'],
                ], $h_tag);
                if ($used_first) {
                    $precloud[] = $entry;
                    continue;
                }
            } else {
                $entry = SPAN([
                    'onclick' => $js,
                    'style' => "font-size: {$size}em$color",
                    'title' => $row['count'],
                ], $h_tag);
            }

            if ($counter++ <= $def_count) {
                $cloud[] = $entry;
            } else {
                $postcloud[] = $entry;
            }
        }

        $html = emptyHTML();
        if (count($precloud) > 0) {
            $html->appendChild(DIV(
                ["id" => "tagcloud_set"],
                SPAN(["class" => "tag-category"], joinHTML(" ", $precloud))
            ));
        }
        $html->appendChild(DIV(
            ["id" => "tagcloud_unset"],
            SPAN(["class" => "tag-category"], joinHTML(" ", $cloud)),
            count($postcloud) > 0 ? DIV(
                ["id" => "tagcloud_extra", "style" => "display: none;"],
                SPAN(["class" => "tag-category"], joinHTML(" ", $postcloud))
            ) : null
        ));
        if ($sort_method !== 'a' && $counter > $def_count) {
            $rem = $counter - $def_count;
            $html->appendChild(emptyHTML(
                BR(),
                "[",
                SPAN(["onclick" => "tageditcloud_toggle_extra(this);", "style" => "color: #0000EF; font-weight:bold;"], "show {$rem} more tags"),
                "]"
            ));
        }

        return DIV(["id" => "tageditcloud", "class" => "tageditcloud"], $html);
    }

}
