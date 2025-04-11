<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, DIV, SPAN, emptyHTML};

use MicroHTML\HTMLElement;

final class TagEditCloud extends Extension
{
    public const KEY = "tag_editcloud";

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        if ($this->can_tag($event->image)) {
            $html = $this->build_tag_map($event->image);
            if (!is_null($html)) {
                $event->add_part($html, 40);
            }
        }
    }

    private function build_tag_map(Image $image): ?HTMLElement
    {
        global $database;

        $sort_method = Ctx::$config->get(TagEditCloudConfig::SORT);
        $tags_min = Ctx::$config->get(TagEditCloudConfig::MIN_USAGE);
        $used_first = Ctx::$config->get(TagEditCloudConfig::USED_FIRST);
        $max_count = Ctx::$config->get(TagEditCloudConfig::MAX_COUNT);
        $def_count = Ctx::$config->get(TagEditCloudConfig::DEF_COUNT);
        $ignore_tags = Tag::explode(Ctx::$config->get(TagEditCloudConfig::IGNORE_TAGS));

        $cat_color = [];
        if (TagCategoriesInfo::is_enabled()) {
            $categories = $database->get_all("SELECT category, color FROM image_tag_categories");
            foreach ($categories as $row) {
                $cat_color[$row['category']] = $row['color'];
            }
        }

        switch ($sort_method) {
            case 'r':
                $relevant_tags = array_diff($image->get_tag_array(), $ignore_tags);
                if (count($relevant_tags) === 0) {
                    return null;
                }
                $relevant_tag_ids = implode(',', array_map(fn ($t) => Tag::get_or_create_id($t), $relevant_tags));

                $tag_data = Ctx::$database->get_all(
                    // @phpstan-ignore-next-line
                    "
					SELECT t2.tag AS tag, COUNT(image_id) AS count, FLOOR(LN(LN(COUNT(image_id) - :tag_min1 + 1)+1)*150)/200 AS scaled
					FROM image_tags it1
					JOIN image_tags it2 USING(image_id)
					JOIN tags t1 ON it1.tag_id = t1.id
					JOIN tags t2 ON it2.tag_id = t2.id
					WHERE t1.count >= :tag_min2 AND t1.id IN ($relevant_tag_ids)
					GROUP BY t2.tag
					ORDER BY count DESC
					LIMIT :limit",
                    ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                );
                break;
                /** @noinspection PhpMissingBreakStatementInspection */
            case 'c':
                if (TagCategoriesInfo::is_enabled()) {
                    $tag_data = $database->get_all(
                        "
                        SELECT tag, FLOOR(LN(LN(count - :tag_min1 + 1)+1)*150)/200 AS scaled, count
                        FROM tags
                        WHERE count >= :tag_min2
                        ORDER BY SUM(count) OVER (PARTITION BY SUBSTRING_INDEX(tag, ':', 1)) DESC, CASE
                            WHEN tag LIKE '%:%' THEN 1
                            ELSE 2
                        END, tag
                        LIMIT :limit",
                        ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                    );
                    break;
                } else {
                    $sort_method = 'a';
                }
                // no break
            case 'a':
            case 'p':
            default:
                $order_by = $sort_method === 'a' ? "tag" : "count DESC";
                $tag_data = $database->get_all(
                    "
					SELECT tag, FLOOR(LN(LN(count - :tag_min1 + 1)+1)*150)/200 AS scaled, count
					FROM tags
					WHERE count >= :tag_min2
					ORDER BY $order_by
					LIMIT :limit",
                    ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                );
                break;
        }

        $cloud = emptyHTML();
        $precloud = emptyHTML();
        $postcloud = emptyHTML();

        $counter = 1;
        $last_cat = null;
        $last_used_cat = null;
        foreach ($tag_data as $row) {
            $full_tag = $row['tag'];

            $current_cat = "";
            if (TagCategoriesInfo::is_enabled()) {
                $tc = explode(':', $row['tag']);
                if (isset($tc[1]) && isset($cat_color[$tc[0]])) {
                    $current_cat = $tc[0];
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

            if (in_array($row['tag'], $image->get_tag_array())) {
                $entry = SPAN([
                    'onclick' => $js,
                    'class' => 'tag-selected',
                    'style' => "font-size: {$size}em$color",
                    'title' => $row['count'],
                ], $h_tag);
                if ($used_first) {
                    if ($last_used_cat !== $current_cat && $last_used_cat !== null) {
                        //$precloud .= "</span><span class='tag-category'>\n";
                    }
                    $last_used_cat = $current_cat;
                    $precloud->appendChild($entry);
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
                if ($last_cat !== $current_cat && $last_cat !== null) {
                    //$cloud .= "</span><span class='tag-category'>\n";
                } //TODO: Maybe add a title for the category after the span opens?
                $cloud->appendChild($entry);
            } else {
                if ($last_cat !== $current_cat && $counter !== $def_count + 2) {
                    //$postcloud .= "</span><span class='tag-category'>\n";
                }
                $postcloud->appendChild($entry);
            }

            $last_cat = $current_cat;
        }

        if (strlen((string)$postcloud) > 0) {
            $postcloud = DIV(
                ["id" => "tagcloud_extra", "style" => "display: none;"],
                SPAN(["class" => "tag-category"], $postcloud)
            );
        }

        $html = emptyHTML();
        if (strlen((string)$precloud) > 0) {
            $html->appendChild(DIV(
                ["id" => "tagcloud_set"],
                SPAN(["class" => "tag-category"], $precloud)
            ));
        }
        $html->appendChild(DIV(
            ["id" => "tagcloud_unset"],
            SPAN(["class" => "tag-category"], $cloud),
            $postcloud
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

    private function can_tag(Image $image): bool
    {
        return (
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG)
            && (
                !$image->is_locked()
                || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)
            )
        );
    }
}
