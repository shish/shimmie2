<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A,B,BR,IMG,emptyHTML,joinHTML,LINK};
use function MicroHTML\INPUT;
use function MicroHTML\LABEL;
use function MicroHTML\OPTION;
use function MicroHTML\SELECT;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TEXTAREA;
use function MicroHTML\TH;
use function MicroHTML\TR;

class CommonElementsTheme extends Themelet
{
    public function build_tag(
        string $tag,
        bool $show_underscores = true,
        bool $show_category = true,
        ?string $style = null,
    ): HTMLElement {
        $props = [
            "href" => search_link([$tag]),
            "class" => "tag",
            "style" => $style,
            "title" => "View all posts tagged $tag"
        ];
        $body = $tag;

        if (TagCategoriesInfo::is_enabled()) {
            $category = TagCategories::get_tag_category($tag);
            if (!is_null($category)) {
                $tag_category_dict = TagCategories::getKeyedDict();
                $props["class"] = "tag tag_category_$category";
                $props["style"] = "color:".$tag_category_dict[$category]['color'].";";

                if ($show_category === false) {
                    $body = TagCategories::get_tag_body($tag);
                }
            }
        }

        $body = $show_underscores ? $body : str_replace("_", " ", $body);

        return A($props, $body);
    }

    /**
     * Generic thumbnail code; returns HTML rather than adding
     * a block since thumbs tend to go inside blocks...
     */
    public function build_thumb(Image $image): HTMLElement
    {
        global $config;

        $id = $image->id;
        $view_link = make_link('post/view/'.$id);
        $thumb_link = $image->get_thumb_link();
        $tip = $image->get_tooltip();
        $tags = strtolower($image->get_tag_list());

        // TODO: Set up a function for fetching what kind of files are currently thumbnailable
        $mimeArr = array_flip([MimeType::MP3]); //List of thumbless filetypes
        if (!isset($mimeArr[$image->get_mime()])) {
            $tsize = ThumbnailUtil::get_thumbnail_size($image->width, $image->height);
        } else {
            //Use max thumbnail size if using thumbless filetype
            $tsize = ThumbnailUtil::get_thumbnail_size($config->get_int(ThumbnailConfig::WIDTH), $config->get_int(ThumbnailConfig::WIDTH));
        }

        $custom_classes = "";
        if (RelationshipsInfo::is_enabled()) {
            if ($image['parent_id'] !== null) {
                $custom_classes .= "shm-thumb-has_parent ";
            }
            if ($image['has_children']) {
                $custom_classes .= "shm-thumb-has_child ";
            }
        }
        if (RatingsInfo::is_enabled() && RatingsBlurInfo::is_enabled()) {
            $rb = new RatingsBlur();
            if ($rb->blur($image['rating'])) {
                $custom_classes .= "blur ";
            }
        }

        $attrs = [
            "href" => $view_link,
            "class" => "thumb shm-thumb shm-thumb-link $custom_classes",
            "data-tags" => $tags,
            "data-height" => $image->height,
            "data-width" => $image->width,
            "data-mime" => $image->get_mime(),
            "data-post-id" => $id,
        ];
        if (RatingsInfo::is_enabled()) {
            $attrs["data-rating"] = $image['rating'];
        }

        return A(
            $attrs,
            IMG(
                [
                    "id" => "thumb_$id",
                    "title" => $tip,
                    "alt" => $tip,
                    "height" => $tsize[1],
                    "width" => $tsize[0],
                    "src" => $thumb_link,
                ]
            )
        );
    }

    public function display_paginator(Page $page, string $base, ?string $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        if ($total_pages == 0) {
            $total_pages = 1;
        }
        $body = $this->build_paginator($page_number, $total_pages, $base, $query, $show_random);
        $page->add_block(new Block(null, $body, "main", 90, "paginator"));

        $page->add_html_header(LINK(['rel' => 'first', 'href' => make_link($base.'/1', $query)]));
        if ($page_number < $total_pages) {
            $page->add_html_header(LINK(['rel' => 'prefetch', 'href' => make_link($base.'/'.($page_number + 1), $query)]));
            $page->add_html_header(LINK(['rel' => 'next', 'href' => make_link($base.'/'.($page_number + 1), $query)]));
        }
        if ($page_number > 1) {
            $page->add_html_header(LINK(['rel' => 'previous', 'href' => make_link($base.'/'.($page_number - 1), $query)]));
        }
        $page->add_html_header(LINK(['rel' => 'last', 'href' => make_link($base.'/'.$total_pages, $query)]));
    }

    private function gen_page_link(string $base_url, ?string $query, int $page, string $name): HTMLElement
    {
        return A(["href" => make_link($base_url.'/'.$page, $query)], $name);
    }

    private function gen_page_link_block(string $base_url, ?string $query, int $page, int $current_page, string $name): HTMLElement
    {
        $paginator = $this->gen_page_link($base_url, $query, $page, $name);
        if ($page == $current_page) {
            $paginator = B($paginator);
        }
        return $paginator;
    }

    private function build_paginator(int $current_page, int $total_pages, string $base_url, ?string $query, bool $show_random): HTMLElement
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;

        $at_start = ($current_page <= 1 || $total_pages <= 1);
        $at_end = ($current_page >= $total_pages);

        $first_html  = $at_start ? "First" : $this->gen_page_link($base_url, $query, 1, "First");
        $prev_html   = $at_start ? "Prev" : $this->gen_page_link($base_url, $query, $prev, "Prev");

        $random_html = "-";
        if ($show_random) {
            $rand = mt_rand(1, $total_pages);
            $random_html =                   $this->gen_page_link($base_url, $query, $rand, "Random");
        }

        $next_html   = $at_end ? "Next" : $this->gen_page_link($base_url, $query, $next, "Next");
        $last_html   = $at_end ? "Last" : $this->gen_page_link($base_url, $query, $total_pages, "Last");

        $start = max($current_page - 5, 1);
        $end = min($start + 10, $total_pages);

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, (string)$i);
        }
        $pages_html = joinHTML(" | ", $pages);

        return emptyHTML(
            joinHTML(" | ", [
                $first_html,
                $prev_html,
                $random_html,
                $next_html,
                $last_html,
            ]),
            BR(),
            '<< ',
            $pages_html,
            ' >>'
        );
    }

    public function config_group_to_block(Config $config, BaseConfigGroup $group): ?Block
    {
        global $user;

        $title = trim($group->title ?? implode(" ", \Safe\preg_split('/(?=[A-Z])/', \Safe\preg_replace("/^Shimmie2.(.*?)(User)?Config$/", "\$1", get_class($group)))));
        $fields = $group->get_config_fields();
        $fields = array_filter($fields, fn ($field) => !$field->advanced || @$_GET["advanced"] == "on");
        if (count($fields) == 0) {
            return null;
        }

        $table = TABLE(["class" => "form"]);
        foreach ($fields as $key => $meta) {
            if ($meta->permission && !$user->can($meta->permission)) {
                continue;
            }

            $row = TR(["class" => $meta->advanced ? "advanced" : ""]);
            $row->appendChild(TH(LABEL(["for" => $key], $meta->label)));
            switch ($meta->input) {
                case "bool":
                    $val = $config->get_bool($key);
                    $input = INPUT(["type" => "checkbox", "id" => $key, "name" => "_config_$key", "checked" => $val]);
                    break;
                case "int":
                    $val = $config->get_int($key);
                    $input = INPUT(["type" => "number", "id" => $key, "name" => "_config_$key", "value" => $val, "size" => 4, "step" => 1]);
                    break;
                case "shorthand_int":
                    $val = to_shorthand_int($config->get_int($key, 0));
                    $input = INPUT(["type" => "text", "id" => $key, "name" => "_config_$key", "value" => $val, "size" => 6]);
                    break;
                case "text":
                    $val = $config->get_string($key);
                    if ($meta->options) {
                        $options = $meta->options;
                        if (is_callable($options)) {
                            $options = call_user_func($options);
                        }
                        $input = SELECT(["id" => $key, "name" => "_config_$key"]);
                        foreach ($options as $optname => $optval) {
                            $input->appendChild(OPTION(["value" => $optval, "selected" => $optval == $val ], $optname));
                        }
                    } else {
                        $input = INPUT(["type" => "text", "id" => $key, "name" => "_config_$key", "value" => $val]);
                    }
                    break;
                case "longtext":
                    $val = $config->get_string($key, "");
                    $rows = max(3, min(10, count(explode("\n", $val))));
                    $input = TEXTAREA(["rows" => $rows, "id" => $key, "name" => "_config_$key"], $val);
                    break;
                case "color":
                    $val = $config->get_string($key);
                    $input = INPUT(["type" => "color", "id" => $key, "name" => "_config_$key", "value" => $val]);
                    break;
                case "multichoice":
                    $val = $config->get_array($key, []);
                    $input = SELECT(["id" => $key, "name" => "_config_{$key}[]", "multiple" => true, "size" => 5]);
                    $options = $meta->options;
                    if (is_callable($options)) {
                        $options = call_user_func($options);
                    }
                    if (is_string($options)) {
                        throw new \Exception("options are invalid: $options");
                    }
                    foreach ($options as $optname => $optval) {
                        $input->appendChild(OPTION(["value" => $optval, "selected" => in_array($optval, $val)], $optname));
                    }
                    break;
                default:
                    throw new \Exception("Unknown input: {$meta->input}");
            }
            $row->appendChild(TD(
                $input,
                INPUT(["type" => "hidden", "name" => "_type_$key", "value" => strtolower($meta->type->name)])
            ));
            $table->appendChild($row);
            if ($meta->help) {
                $table->appendChild(TR(TD(["colspan" => 2, "style" => "text-align: center;"], "(" . $meta->help . ")")));
            }
        }

        $html = $group->tweak_html($table);

        return new Block($title, $html, "main", $group->position ?? 50);
    }
}
