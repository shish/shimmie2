<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class Themelet
 */
class Themelet extends BaseThemelet
{
    /**
     * Put something in a rounded rectangle box; specific to the default theme.
     */
    public function rr(string $html): string
    {
        return "
			<div class='tframe'>
				$html
			</div>
		";
    }

    public function display_paginator(Page $page, string $base, ?string $query, int $page_number, int $total_pages, bool $show_random = false)
    {
        if ($total_pages == 0) {
            $total_pages = 1;
        }
        $body = $this->litetheme_build_paginator($page_number, $total_pages, $base, $query, $show_random);
        $page->add_block(new Block(null, $body, "main", 90));
    }

    public function litetheme_gen_page_link(string $base_url, ?string $query, int $page, string $name, ?string $link_class=null): string
    {
        $link = make_link("$base_url/$page", $query);
        return "<a class='$link_class' href='$link'>$name</a>";
    }

    public function litetheme_gen_page_link_block(string $base_url, ?string $query, int $page, int $current_page, string $name): string
    {
        $paginator = "";

        if ($page == $current_page) {
            $link_class = "tab-selected";
        } else {
            $link_class = "";
        }
        $paginator .= $this->litetheme_gen_page_link($base_url, $query, $page, $name, $link_class);

        return $paginator;
    }

    public function litetheme_build_paginator(int $current_page, int $total_pages, string $base_url, ?string $query, bool $show_random): string
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;

        $at_start = ($current_page <= 1 || $total_pages <= 1);
        $at_end = ($current_page >= $total_pages);

        $first_html  = $at_start ? "<span class='tab'>First</span>" : $this->litetheme_gen_page_link($base_url, $query, 1, "First");
        $prev_html   = $at_start ? "<span class='tab'>Prev</span>" : $this->litetheme_gen_page_link($base_url, $query, $prev, "Prev");

        $random_html = "";
        if ($show_random) {
            $rand = mt_rand(1, $total_pages);
            $random_html =                                            $this->litetheme_gen_page_link($base_url, $query, $rand, "Random");
        }

        $next_html   = $at_end ? "<span class='tab'>Next</span>" : $this->litetheme_gen_page_link($base_url, $query, $next, "Next");
        $last_html   = $at_end ? "<span class='tab'>Last</span>" : $this->litetheme_gen_page_link($base_url, $query, $total_pages, "Last");

        $start = $current_page-5 > 1 ? $current_page-5 : 1;
        $end = $start+10 < $total_pages ? $start+10 : $total_pages;

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->litetheme_gen_page_link_block($base_url, $query, $i, $current_page, strval($i));
        }
        $pages_html = implode(" ", $pages);

        return "<div class='paginator sfoot'>
			$first_html
			$prev_html
			$random_html
			&lt;&lt; $pages_html &gt;&gt;
			$next_html $last_html
			</div>";
    }
}
