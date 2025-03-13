<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, B, DIV, joinHTML};

class DanbooruCommonElementsTheme extends CommonElementsTheme
{
    /**
     * @param ?query-array $query
     */
    public function display_paginator(Page $page, string $base, ?array $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        if ($total_pages == 0) {
            $total_pages = 1;
        }
        $body = $this->build_paginator($page_number, $total_pages, $base, $query);
        $page->add_block(new Block(null, $body, "main", 90));
    }

    /**
     * @param ?query-array $query
     */
    private function gen_page_link(string $base_url, ?array $query, int $page, string $name): HTMLElement
    {
        return A(["href" => make_link("$base_url/$page", $query)], $name);
    }

    /**
     * @param ?query-array $query
     */
    private function gen_page_link_block(string $base_url, ?array $query, int $page, int $current_page, string $name): HTMLElement
    {
        if ($page == $current_page) {
            $paginator = B($page);
        } else {
            $paginator = $this->gen_page_link($base_url, $query, $page, $name);
        }
        return $paginator;
    }

    /**
     * @param ?query-array $query
     */
    private function build_paginator(int $current_page, int $total_pages, string $base_url, ?array $query): HTMLElement
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;

        $at_start = ($current_page <= 3 || $total_pages <= 3);
        $at_end = ($current_page >= $total_pages - 2);

        $first_html  = $at_start ? "" : $this->gen_page_link($base_url, $query, 1, "1");
        $prev_html   = $at_start ? "" : $this->gen_page_link($base_url, $query, $prev, "<<");
        $next_html   = $at_end ? "" : $this->gen_page_link($base_url, $query, $next, ">>");
        $last_html   = $at_end ? "" : $this->gen_page_link($base_url, $query, $total_pages, "$total_pages");

        $start = $current_page - 2 > 1 ? $current_page - 2 : 1;
        $end   = $current_page + 2 <= $total_pages ? $current_page + 2 : $total_pages;

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, (string)$i);
        }
        $pages_html = joinHTML(" ", $pages);

        if ($first_html) {
            $pdots = "...";
        } else {
            $pdots = "";
        }

        if ($last_html) {
            $ndots = "...";
        } else {
            $ndots = "";
        }

        return DIV(["id" => 'paginator'], joinHTML(" ", [$prev_html, $first_html, $pdots, $pages_html, $ndots, $last_html, $next_html]));
    }
}
