<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\rawHTML;

/* Todo:
 * 	usepref(todo2: port userpref)
 *	theme junk
 */
class TagEditCloud extends Extension
{
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        global $config;

        if (!$config->get_bool("tageditcloud_disable") && $this->can_tag($event->image)) {
            $html = $this->build_tag_map($event->image);
            if (!is_null($html)) {
                $event->add_part($html, 40);
            }
        }
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool("tageditcloud_disable", false);
        $config->set_default_bool("tageditcloud_usedfirst", true);
        $config->set_default_string("tageditcloud_sort", 'a');
        $config->set_default_int("tageditcloud_minusage", 2);
        $config->set_default_int("tageditcloud_defcount", 40);
        $config->set_default_int("tageditcloud_maxcount", 4096);
        $config->set_default_string("tageditcloud_ignoretags", 'tagme');
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sort_by = ['Alphabetical' => 'a','Popularity' => 'p','Relevance' => 'r','Categories' => 'c'];

        $sb = $event->panel->create_new_block("Tag Edit Cloud");
        $sb->add_bool_option("tageditcloud_disable", "Disable Tag Selection Cloud: ");
        $sb->add_choice_option("tageditcloud_sort", $sort_by, "<br>Sort the tags by:");
        $sb->add_bool_option("tageditcloud_usedfirst", "<br>Always show used tags first: ");
        $sb->add_label("<br><b>Alpha sort</b>:<br>Only show tags used at least ");
        $sb->add_int_option("tageditcloud_minusage");
        $sb->add_label(" times.<br><b>Popularity/Relevance sort</b>:<br>Show ");
        $sb->add_int_option("tageditcloud_defcount");
        $sb->add_label(" tags by default.<br>Show a maximum of ");
        $sb->add_int_option("tageditcloud_maxcount");
        $sb->add_label(" tags.");
        $sb->add_label("<br><b>Relevance sort</b>:<br>Ignore tags (space separated): ");
        $sb->add_text_option("tageditcloud_ignoretags");
    }

    private function build_tag_map(Image $image): ?HTMLElement
    {
        global $database, $config;

        $html = "";
        $cloud = "";
        $precloud = "";
        $postcloud = "";

        $sort_method = $config->get_string("tageditcloud_sort");
        $tags_min = $config->get_int("tageditcloud_minusage");
        $used_first = $config->get_bool("tageditcloud_usedfirst");
        $max_count = $config->get_int("tageditcloud_maxcount");
        $def_count = $config->get_int("tageditcloud_defcount");

        $ignore_tags = Tag::explode($config->get_string("tageditcloud_ignoretags"));

        $cat_color = [];
        if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
            $categories = $database->get_all("SELECT category, color FROM image_tag_categories");
            foreach ($categories as $row) {
                $cat_color[$row['category']] = $row['color'];
            }
        }

        switch ($sort_method) {
            case 'r':
                $relevant_tags = array_diff($image->get_tag_array(), $ignore_tags);
                if (count($relevant_tags) == 0) {
                    return null;
                }
                $relevant_tag_ids = implode(',', array_map(fn ($t) => Tag::get_or_create_id($t), $relevant_tags));

                $tag_data = $database->get_all(
                    "
					SELECT t2.tag AS tag, COUNT(image_id) AS count, FLOOR(LN(LN(COUNT(image_id) - :tag_min1 + 1)+1)*150)/200 AS scaled
					FROM image_tags it1
					JOIN image_tags it2 USING(image_id)
					JOIN tags t1 ON it1.tag_id = t1.id
					JOIN tags t2 ON it2.tag_id = t2.id
					WHERE t1.count >= :tag_min2 AND t1.tag_id IN ($relevant_tag_ids)
					GROUP BY t2.tag
					ORDER BY count DESC
					LIMIT :limit",
                    ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                );
                break;
                /** @noinspection PhpMissingBreakStatementInspection */
            case 'c':
                if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
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
                $order_by = $sort_method == 'a' ? "tag" : "count DESC";
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

        $counter = 1;
        $last_cat = null;
        $last_used_cat = null;
        foreach ($tag_data as $row) {
            $full_tag = $row['tag'];

            $current_cat = "";
            if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
                $tc = explode(':', $row['tag']);
                if (isset($tc[1]) && isset($cat_color[$tc[0]])) {
                    $current_cat = $tc[0];
                    $h_tag = html_escape($tc[1]);
                    $color = '; color:'.$cat_color[$tc[0]];
                } else {
                    $h_tag = html_escape($row['tag']);
                    $color = '';
                }
            } else {
                $h_tag = html_escape($row['tag']);
                $color = '';
            }

            $size = sprintf("%.2f", max($row['scaled'], 0.5));
            $js = html_escape('tageditcloud_toggle_tag(this,'.\Safe\json_encode($full_tag).')'); //Ugly, but it works

            if (in_array($row['tag'], $image->get_tag_array())) {
                if ($used_first) {
                    if ($last_used_cat !== $current_cat && $last_used_cat !== null) {
                        $precloud .= "</span><span class='tag-category'>\n";
                    }
                    $last_used_cat = $current_cat;
                    $precloud .= "&nbsp;<span onclick='{$js}' class='tag-selected' style='font-size: {$size}em$color' title='{$row['count']}'>{$h_tag}</span>&nbsp;\n";
                    continue;
                } else {
                    $entry = "&nbsp;<span onclick='{$js}' class='tag-selected' style='font-size: {$size}em$color' title='{$row['count']}'>{$h_tag}</span>&nbsp;\n";
                }
            } else {
                $entry = "&nbsp;<span onclick='{$js}' style='font-size: {$size}em$color' title='{$row['count']}'>{$h_tag}</span>&nbsp;\n";
            }

            if ($counter++ <= $def_count) {
                if ($last_cat !== $current_cat && $last_cat !== null) {
                    $cloud .= "</span><span class='tag-category'>\n";
                } //TODO: Maybe add a title for the category after the span opens?
                $cloud .= $entry;
            } else {
                if ($last_cat !== $current_cat && $counter !== $def_count + 2) {
                    $postcloud .= "</span><span class='tag-category'>\n";
                }
                $postcloud .= $entry;
            }

            $last_cat = $current_cat;
        }

        if ($precloud != '') {
            $html .= "<div id='tagcloud_set'><span class='tag-category'>{$precloud}</span></div>";
        }

        if ($postcloud != '') {
            $postcloud = "<div id='tagcloud_extra' style='display: none;'><span class='tag-category'>{$postcloud}</span></div>";
        }

        $html .= "<div id='tagcloud_unset'><span class='tag-category'>{$cloud}</span>{$postcloud}</div>";

        if ($sort_method != 'a' && $counter > $def_count) {
            $rem = $counter - $def_count;
            $html .= "</div><br>[<span onclick='tageditcloud_toggle_extra(this);' style='color: #0000EF; font-weight:bold;'>show {$rem} more tags</span>]";
        }

        return rawHTML("<div id='tageditcloud' class='tageditcloud'>{$html}</div>"); // FIXME: stupidasallhell
    }

    private function can_tag(Image $image): bool
    {
        global $user;
        return ($user->can(Permissions::EDIT_IMAGE_TAG) && (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)));
    }
}
