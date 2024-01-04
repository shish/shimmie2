<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * Name: Lite Theme
 * Author: Zach Hall <zach@sosguy.net>
 * Link: http://seemslegit.com
 * License: GPLv2
 * Description: A mashup of Default, Danbooru, the interface on qwebirc, and
 * 	       some other sites, packaged in a light blue color.
 */

class Page extends BasePage
{
    public function body_html(): string
    {
        global $config;

        list($nav_links, $sub_links) = $this->get_nav_links();
        $theme_name = $config->get_string(SetupConfig::THEME, 'lite');
        $site_name = $config->get_string(SetupConfig::TITLE);
        $data_href = get_base_href();

        $menu = "<div class='menu'>
			<script type='text/javascript' src='{$data_href}/themes/{$theme_name}/wz_tooltip.js'></script>
			<a href='".make_link()."' onmouseover='Tip(&#39;Home&#39;, BGCOLOR, &#39;#C3D2E0&#39;, FADEIN, 100)' onmouseout='UnTip()'><img alt='' src='{$data_href}/favicon.ico' style='position: relative; top: 3px;'></a>
			<b>{$site_name}</b> ";

        // Custom links: These appear on the menu.
        $custom_links = "";
        foreach ($nav_links as $nav_link) {
            $custom_links .= $this->navlinks($nav_link->link, $nav_link->description, $nav_link->active);
        }
        $menu .= "{$custom_links}</div>";

        $left_block_html = "";
        $main_block_html = "";
        $sub_block_html  = "";
        $user_block_html = "";

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html .= $this->block_to_html($block, true);
                    break;
                case "main":
                    $main_block_html .= $this->block_to_html($block, false);
                    break;
                case "user":
                    $user_block_html .= $block->body;
                    break;
                case "subheading":
                    $sub_block_html .= $this->block_to_html($block, false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $custom_sublinks = "";
        if (!empty($sub_links)) {
            $custom_sublinks = "<div class='sbar'>";
            foreach ($sub_links as $nav_link) {
                $custom_sublinks .= $this->navlinks($nav_link->link, $nav_link->description, $nav_link->active);
            }
            $custom_sublinks .= "</div>";
        }

        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";

        if (!$this->left_enabled) {
            $left_block_html = "";
            $main_block_html = "<article id='body_noleft'>{$main_block_html}</article>";
        } else {
            $left_block_html = "<nav>{$left_block_html}</nav>";
            $main_block_html = "<article>$flash_html{$main_block_html}</article>";
        }

        $footer_html = $this->footer_html();

        return <<<EOD
		<header>
			$menu
			$custom_sublinks
			$sub_block_html
		</header>
		$left_block_html
		$main_block_html
		<footer>
		    $footer_html
		</footer>
EOD;
    } /* end of function display_page() */

    public function block_to_html(Block $block, bool $hidable = false): string
    {
        $h = $block->header;
        $b = $block->body;
        $i = $block->id;
        $html = $b;
        if ($h != "Paginator") {
            $html = "<section id='{$i}'>";
            if (!is_null($h)) {
                $html .= "<div class='navtop navside tab shm-toggler' data-toggle-sel='#{$i}'>{$h}</div>";
            }
            if (!is_null($b)) {
                $html .= "<div class='navside tab".($hidable ? " blockbody" : "")."'>$b</div>";
            }
            $html .= "</section>";
        }
        return $html;
    }

    public function navlinks(Link $link, HTMLElement|string $desc, bool $active): ?string
    {
        $html = null;
        if ($active) {
            $html = "<a class='tab-selected' href='{$link->make_link()}'>{$desc}</a>";
        } else {
            $html = "<a class='tab' href='{$link->make_link()}'>{$desc}</a>";
        }

        return $html;
    }
}
