<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, P, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class FutabaCommonElementsTheme extends CommonElementsTheme
{
    public function display_paginator(string $base, ?QueryArray $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        if ($total_pages === 0) {
            $total_pages = 1;
        }
        $body = $this->futaba_build_paginator($page_number, $total_pages, $base, $query);
        Ctx::$page->add_block(new Block(null, $body, "main", 90));
    }

    public function futaba_gen_page_link(string $base_url, ?QueryArray $query, int $page, string $name): HTMLElement
    {
        return emptyHTML("[", A(["href" => make_link("$base_url/$page", $query)], $name), "]");
    }

    public function futaba_gen_page_link_block(string $base_url, ?QueryArray $query, int $page, int $current_page, string $name): HTMLElement
    {
        $paginator = $this->futaba_gen_page_link($base_url, $query, $page, $name);
        if ($page === $current_page) {
            $paginator = B($paginator);
        }
        return $paginator;
    }

    public function futaba_build_paginator(int $current_page, int $total_pages, string $base_url, ?QueryArray $query): HTMLElement
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;

        $at_start = ($current_page <= 1 || $total_pages <= 1);
        $at_end = ($current_page >= $total_pages);

        $prev_html = $at_start ? "Prev" : $this->futaba_gen_page_link($base_url, $query, $prev, "Prev");
        $next_html = $at_end ? "Next" : $this->futaba_gen_page_link($base_url, $query, $next, "Next");

        $start = $current_page - 5 > 1 ? $current_page - 5 : 1;
        $end = $start + 10 < $total_pages ? $start + 10 : $total_pages;

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->futaba_gen_page_link_block($base_url, $query, $i, $current_page, (string)$i);
        }

        return P(["class" => "paginator"], $prev_html, joinHTML(" ", $pages), $next_html);
    }
}
