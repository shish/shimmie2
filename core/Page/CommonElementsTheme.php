<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, IMG, LINK, SPAN, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT, LABEL, OPTION, SELECT, TABLE, TD, TEXTAREA, TH, TR};

class CommonElementsTheme extends Themelet
{
    /**
     * @param tag-string $tag
     */
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
        $id = $image->id;
        $view_link = make_link('post/view/'.$id);
        $thumb_link = $image->get_thumb_link();
        $tip = $image->get_tooltip();
        $tags = strtolower($image->get_tag_list());
        $tsize = $image->get_thumb_size();

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
        if (NotesInfo::is_enabled()) {
            $attrs["data-notes"] = $image['notes'];
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

    public function build_navigation(Navigation $nav): HTMLElement
    {
        $html = emptyHTML();
        if ($nav->isPaginated) {
            $html->appendChild(joinHTML(" | ", [
                $nav->prev === null ? "Prev" : A(["href" => $nav->prev, "class" => "prevlink"], "Prev"),
                A(["href" => $nav->index ?? make_link()], "Index"),
                $nav->next === null ? "Next" : A(["href" => $nav->next, "class" => "nextlink"], "Next"),
            ]));
        } else {
            $html->appendChild(A(["href" => $nav->index ?? make_link()], "Index"));
        }

        if (\count($nav->extras) > 0) {
            usort($nav->extras, fn ($a, $b) => $a[1] - $b[1]);
            $html->appendChild(BR(), joinHTML(BR(), array_column($nav->extras, "0")));
        }

        return $html;
    }

    public function build_navlink(NavLink $navlink): HTMLElement
    {
        return A(
            [
                "href" => $navlink->link,
                ... $navlink->active ? ["class" => "active"] : [],
            ],
            SPAN($navlink->description)
        );
    }

    public function display_paginator(string $base, ?QueryArray $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        if ($total_pages === 0) {
            $total_pages = 1;
        }
        $body = $this->build_paginator($page_number, $total_pages, $base, $query, $show_random);
        Ctx::$page->add_block(new Block(null, $body, "main", 90, "paginator"));

        Ctx::$page->add_html_header(LINK(['rel' => 'first', 'href' => make_link($base.'/1', $query)]));
        if ($page_number < $total_pages) {
            Ctx::$page->add_html_header(LINK(['rel' => 'prefetch', 'href' => make_link($base.'/'.($page_number + 1), $query)]));
            Ctx::$page->add_html_header(LINK(['rel' => 'next', 'href' => make_link($base.'/'.($page_number + 1), $query)]));
        }
        if ($page_number > 1) {
            Ctx::$page->add_html_header(LINK(['rel' => 'previous', 'href' => make_link($base.'/'.($page_number - 1), $query)]));
        }
        Ctx::$page->add_html_header(LINK(['rel' => 'last', 'href' => make_link($base.'/'.$total_pages, $query)]));
    }

    private function gen_page_link(string $base_url, ?QueryArray $query, int $page, string $name): HTMLElement
    {
        return A(["href" => make_link($base_url.'/'.$page, $query)], $name);
    }

    private function gen_page_link_block(string $base_url, ?QueryArray $query, int $page, int $current_page, string $name): HTMLElement
    {
        $paginator = $this->gen_page_link($base_url, $query, $page, $name);
        if ($page === $current_page) {
            $paginator = B($paginator);
        }
        return $paginator;
    }

    private function build_paginator(int $current_page, int $total_pages, string $base_url, ?QueryArray $query, bool $show_random): HTMLElement
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
        $title = $group->get_title();
        $fields = $group->get_config_fields();
        $fields = array_filter($fields, fn ($field) => !$field->advanced || @$_GET["advanced"] === "on");
        if (count($fields) === 0) {
            return null;
        }

        $table = TABLE(["class" => "form"]);
        foreach ($fields as $key => $meta) {
            if ($meta->permission && !Ctx::$user->can($meta->permission)) {
                continue;
            }

            $row = TR(["class" => $meta->advanced ? "advanced" : ""]);
            $row->appendChild(TH(LABEL(["for" => $key], $meta->label)));
            switch ($meta->input) {
                case ConfigInput::CHECKBOX:
                    $val = $config->get($key, ConfigType::BOOL);
                    $input = INPUT(["type" => "checkbox", "id" => $key, "name" => "_config_$key", "checked" => $val]);
                    break;
                case ConfigInput::NUMBER:
                    $val = $config->get($key, ConfigType::INT);
                    $input = INPUT(["type" => "number", "id" => $key, "name" => "_config_$key", "value" => $val, "size" => 4, "step" => 1]);
                    break;
                case ConfigInput::BYTES:
                    $val = to_shorthand_int($config->get($key, ConfigType::INT) ?? 0);
                    $input = INPUT(["type" => "text", "id" => $key, "name" => "_config_$key", "value" => $val, "size" => 6]);
                    break;
                case ConfigInput::TEXT:
                    $val = $config->get($key, ConfigType::STRING);
                    if ($meta->options) {
                        $options = $meta->options;
                        if (is_callable($options)) {
                            $options = call_user_func($options);
                        }
                        $input = SELECT(["id" => $key, "name" => "_config_$key"]);
                        foreach ($options as $optname => $optval) {
                            $input->appendChild(OPTION(["value" => $optval, "selected" => $optval === $val ], $optname));
                        }
                    } else {
                        $input = INPUT(["type" => "text", "id" => $key, "name" => "_config_$key", "value" => $val]);
                    }
                    break;
                case ConfigInput::TEXTAREA:
                    $val = $config->get($key, ConfigType::STRING) ?? "";
                    $rows = max(3, min(10, count(explode("\n", $val))));
                    $input = TEXTAREA(["rows" => $rows, "id" => $key, "name" => "_config_$key"], $val);
                    break;
                case ConfigInput::COLOR:
                    $val = $config->get($key, ConfigType::STRING);
                    $input = INPUT(["type" => "color", "id" => $key, "name" => "_config_$key", "value" => $val]);
                    break;
                case ConfigInput::MULTICHOICE:
                    $val = $config->get($key, ConfigType::ARRAY) ?? [];
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
