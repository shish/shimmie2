<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A,DIV,SPAN,joinHTML};

class Themelet extends BaseThemelet
{
    public function display_paginator(Page $page, string $base, ?string $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        if ($total_pages == 0) {
            $total_pages = 1;
        }
        $body = $this->litetheme_build_paginator($page_number, $total_pages, $base, $query, $show_random);
        $page->add_block(new Block("Paginator", $body, "main", 90));
    }

    public function litetheme_gen_page_link(string $base_url, ?string $query, int $page, string $name, ?string $link_class = null): HTMLElement
    {
        return A(["href" => make_link("$base_url/$page", $query), "class" => $link_class], $name);
    }

    public function litetheme_gen_page_link_block(string $base_url, ?string $query, int $page, int $current_page, string $name): HTMLElement
    {
        if ($page == $current_page) {
            $link_class = "tab-selected";
        } else {
            $link_class = "";
        }
        return $this->litetheme_gen_page_link($base_url, $query, $page, $name, $link_class);
    }

    public function litetheme_build_paginator(int $current_page, int $total_pages, string $base_url, ?string $query, bool $show_random): HTMLElement
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;

        $at_start = ($current_page <= 1 || $total_pages <= 1);
        $at_end = ($current_page >= $total_pages);

        $first_html  = $at_start ? SPAN(["class" => "tab"], "First") : $this->litetheme_gen_page_link($base_url, $query, 1, "First");
        $prev_html   = $at_start ? SPAN(["class" => "tab"], "Prev") : $this->litetheme_gen_page_link($base_url, $query, $prev, "Prev");

        $random_html = "";
        if ($show_random) {
            $rand = mt_rand(1, $total_pages);
            $random_html = $this->litetheme_gen_page_link($base_url, $query, $rand, "Random");
        }

        $next_html   = $at_end ? SPAN(["class" => "tab"], "Next") : $this->litetheme_gen_page_link($base_url, $query, $next, "Next");
        $last_html   = $at_end ? SPAN(["class" => "tab"], "Last") : $this->litetheme_gen_page_link($base_url, $query, $total_pages, "Last");

        $start = $current_page - 5 > 1 ? $current_page - 5 : 1;
        $end = $start + 10 < $total_pages ? $start + 10 : $total_pages;

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->litetheme_gen_page_link_block($base_url, $query, $i, $current_page, strval($i));
        }

        return DIV(
            ["class" => "paginator sfoot"],
            $first_html,
            $prev_html,
            $random_html,
            "<< ",
            joinHTML(" ", $pages),
            " >>",
            $next_html,
            $last_html
        );
    }
}
