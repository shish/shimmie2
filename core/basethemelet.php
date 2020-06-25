<?php declare(strict_types=1);

/**
 * Class BaseThemelet
 *
 * A collection of common functions for theme parts
 */
class BaseThemelet
{

    /**
     * Generic error message display
     */
    public function display_error(int $code, string $title, string $message): void
    {
        global $page;
        $page->set_code($code);
        $page->set_title($title);
        $page->set_heading($title);
        $has_nav = false;
        foreach ($page->blocks as $block) {
            if ($block->header == "Navigation") {
                $has_nav = true;
                break;
            }
        }
        if (!$has_nav) {
            $page->add_block(new NavBlock());
        }
        $page->add_block(new Block("Error", $message));
    }

    /**
     * A specific, common error message
     */
    public function display_permission_denied(): void
    {
        $this->display_error(403, "Permission Denied", "You do not have permission to access this page");
    }


    /**
     * Generic thumbnail code; returns HTML rather than adding
     * a block since thumbs tend to go inside blocks...
     */
    public function build_thumb_html(Image $image): string
    {
        global $config;

        $i_id = (int) $image->id;
        $h_view_link = make_link('post/view/'.$i_id);
        $h_thumb_link = $image->get_thumb_link();
        $h_tip = html_escape($image->get_tooltip());
        $h_tags = html_escape(strtolower($image->get_tag_list()));

        // TODO: Set up a function for fetching what kind of files are currently thumbnailable
        $mimeArr = array_flip([MimeType::MP3]); //List of thumbless filetypes
        if (!isset($mimeArr[$image->get_mime()])) {
            $tsize = get_thumbnail_size($image->width, $image->height);
        } else {
            //Use max thumbnail size if using thumbless filetype
            $tsize = get_thumbnail_size($config->get_int(ImageConfig::THUMB_WIDTH), $config->get_int(ImageConfig::THUMB_WIDTH));
        }

        $custom_classes = "";
        if (class_exists("Relationships")) {
            if (property_exists($image, 'parent_id') && $image->parent_id !== null) {
                $custom_classes .= "shm-thumb-has_parent ";
            }
            if (property_exists($image, 'has_children') && bool_escape($image->has_children)) {
                $custom_classes .= "shm-thumb-has_child ";
            }
        }

        return "<a href='$h_view_link' class='thumb shm-thumb shm-thumb-link {$custom_classes}' data-tags='$h_tags' data-height='$image->height' data-width='$image->width' data-post-id='$i_id'>".
                "<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' src='$h_thumb_link'>".
                "</a>\n";
    }

    public function display_paginator(Page $page, string $base, ?string $query, int $page_number, int $total_pages, bool $show_random = false)
    {
        if ($total_pages == 0) {
            $total_pages = 1;
        }
        $body = $this->build_paginator($page_number, $total_pages, $base, $query, $show_random);
        $page->add_block(new Block(null, $body, "main", 90, "paginator"));

        $page->add_html_header("<link rel='first' href='".make_http(make_link($base.'/1', $query))."'>");
        if ($page_number < $total_pages) {
            $page->add_html_header("<link rel='prefetch' href='".make_http(make_link($base.'/'.($page_number+1), $query))."'>");
            $page->add_html_header("<link rel='next' href='".make_http(make_link($base.'/'.($page_number+1), $query))."'>");
        }
        if ($page_number > 1) {
            $page->add_html_header("<link rel='previous' href='".make_http(make_link($base.'/'.($page_number-1), $query))."'>");
        }
        $page->add_html_header("<link rel='last' href='".make_http(make_link($base.'/'.$total_pages, $query))."'>");
    }

    private function gen_page_link(string $base_url, ?string $query, int $page, string $name): string
    {
        $link = make_link($base_url.'/'.$page, $query);
        return '<a href="'.$link.'">'.$name.'</a>';
    }

    private function gen_page_link_block(string $base_url, ?string $query, int $page, int $current_page, string $name): string
    {
        $paginator = "";
        if ($page == $current_page) {
            $paginator .= "<b>";
        }
        $paginator .= $this->gen_page_link($base_url, $query, $page, $name);
        if ($page == $current_page) {
            $paginator .= "</b>";
        }
        return $paginator;
    }

    private function build_paginator(int $current_page, int $total_pages, string $base_url, ?string $query, bool $show_random): string
    {
        $next = $current_page + 1;
        $prev = $current_page - 1;

        $at_start = ($current_page <= 1 || $total_pages <= 1);
        $at_end = ($current_page >= $total_pages);

        $first_html  = $at_start ? "First" : $this->gen_page_link($base_url, $query, 1, "First");
        $prev_html   = $at_start ? "Prev"  : $this->gen_page_link($base_url, $query, $prev, "Prev");

        $random_html = "-";
        if ($show_random) {
            $rand = mt_rand(1, $total_pages);
            $random_html =                   $this->gen_page_link($base_url, $query, $rand, "Random");
        }

        $next_html   = $at_end   ? "Next"  : $this->gen_page_link($base_url, $query, $next, "Next");
        $last_html   = $at_end   ? "Last"  : $this->gen_page_link($base_url, $query, $total_pages, "Last");

        $start = $current_page-5 > 1 ? $current_page-5 : 1;
        $end = $start+10 < $total_pages ? $start+10 : $total_pages;

        $pages = [];
        foreach (range($start, $end) as $i) {
            $pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, (string)$i);
        }
        $pages_html = implode(" | ", $pages);

        return $first_html.' | '.$prev_html.' | '.$random_html.' | '.$next_html.' | '.$last_html
                .'<br>&lt;&lt; '.$pages_html.' &gt;&gt;';
    }
}
