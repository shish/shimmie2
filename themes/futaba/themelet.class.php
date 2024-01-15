<?php

declare(strict_types=1);

namespace Shimmie2;

class Themelet extends BaseThemelet
{
    /**
     * Add a generic paginator.
     */
    public function display_paginator(Page $page, string $base, ?string $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        if ($total_pages == 0) {
            $total_pages = 1;
        }
        $body = $this->futaba_build_paginator($page_number, $total_pages, $base, $query);
        $page->add_block(new Block(null, $body, "main", 90));
    }

    /**
     * Generate a single HTML link.
     */
    public function futaba_gen_page_link(string $base_url, ?string $query, int $page, string $name): string
    {
        $link = make_link("$base_url/$page", $query);
        return "[<a href='$link'>{$name}</a>]";
    }

    public function futaba_gen_page_link_block(string $base_url, ?string $query, int $page, int $current_page, string $name): string
    {
        $paginator = "";
        if ($page == $current_page) {
            $paginator .= "<b>";
        }
        $paginator .= $this->futaba_gen_page_link($base_url, $query, $page, $name);
        if ($page == $current_page) {
            $paginator .= "</b>";
        }
        return $paginator;
    }

    public function futaba_build_paginator(int $current_page, int $total_pages, string $base_url, ?string $query): string
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;
        //$rand = mt_rand(1, $total_pages);

        $at_start = ($current_page <= 1 || $total_pages <= 1);
        $at_end = ($current_page >= $total_pages);

        //$first_html   = $at_start ? "First" : $this->futaba_gen_page_link($base_url, $query, 1,            "First");
        $prev_html      = $at_start ? "Prev" : $this->futaba_gen_page_link($base_url, $query, $prev, "Prev");
        //$random_html  =                       $this->futaba_gen_page_link($base_url, $query, $rand,        "Random");
        $next_html      = $at_end ? "Next" : $this->futaba_gen_page_link($base_url, $query, $next, "Next");
        //$last_html    = $at_end   ? "Last"  : $this->futaba_gen_page_link($base_url, $query, $total_pages, "Last");

        $start = $current_page - 5 > 1 ? $current_page - 5 : 1;
        $end = $start + 10 < $total_pages ? $start + 10 : $total_pages;

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->futaba_gen_page_link_block($base_url, $query, $i, $current_page, (string)$i);
        }
        $pages_html = implode(" ", $pages);

        //return "<p class='paginator'>$first_html | $prev_html | $random_html | $next_html | $last_html".
        //		"<br>&lt;&lt; $pages_html &gt;&gt;</p>";
        return "<p class='paginator'>{$prev_html} {$pages_html} {$next_html}</p>";
    }
}
