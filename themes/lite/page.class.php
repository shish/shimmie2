<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{BODY, emptyHTML, HEADER, FOOTER, DIV, SCRIPT, A, B, IMG, NAV, ARTICLE, rawHTML, SECTION};

/**
 * Name: Lite Theme
 * Author: Zach Hall <zach@sosguy.net>
 * Link: http://seemslegit.com
 * License: GPLv2
 * Description: A mashup of Default, Danbooru, the interface on qwebirc, and
 * 	       some other sites, packaged in a light blue color.
 */

class LitePage extends Page
{
    protected function body_html(): HTMLElement
    {
        global $config;

        list($nav_links, $sub_links) = $this->get_nav_links();
        $theme_name = $config->get_string(SetupConfig::THEME, 'lite');
        $site_name = $config->get_string(SetupConfig::TITLE);
        $data_href = get_base_href();

        $menu = DIV(
            ["class" => "menu"],
            SCRIPT(["type" => "text/javascript", "src" => "{$data_href}/themes/{$theme_name}/wz_tooltip.js"]),
            A(
                [
                    "href" => make_link(),
                    "onmouseover" => 'Tip("Home", BGCOLOR, "#C3D2E0", FADEIN, 100)',
                    "onmouseout" => 'UnTip()'
                ],
                IMG(["alt" => "", "src" => "{$data_href}/favicon.ico", "style" => "position: relative; top: 3px;"])
            ),
            B($site_name)
        );

        // Custom links: These appear on the menu.
        $custom_links = DIV(["class" => "bar"]);
        foreach ($nav_links as $nav_link) {
            $custom_links->appendChild($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active));
        }
        $menu->appendChild($custom_links);

        $left_block_html = [];
        $main_block_html = [];
        $sub_block_html  = [];
        $user_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "main":
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                case "user":
                    $user_block_html[] = $block->body;
                    break;
                case "subheading":
                    $sub_block_html[] = $this->block_html($block, false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $custom_sublinks = null;
        if (!empty($sub_links)) {
            $custom_sublinks = DIV(["class" => "sbar"]);
            foreach ($sub_links as $nav_link) {
                $custom_sublinks->appendChild($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active));
            }
        }

        $flash_html = $this->flash_html();
        $footer_html = $this->footer_html();

        return BODY(
            $this->body_attrs(),
            HEADER(
                $menu,
                $custom_sublinks,
                ...$sub_block_html
            ),
            NAV(...$left_block_html),
            ARTICLE(
                $flash_html,
                ...$main_block_html
            ),
            FOOTER($footer_html)
        );
    } /* end of function display_page() */

    protected function block_html(Block $block, bool $hidable = false): HTMLElement
    {
        $h = $block->header;
        $i = $block->id;
        if ($h == "Paginator") {
            return $block->body;
        }
        $html = SECTION(["id" => $i]);
        if (!is_null($block->header)) {
            $html->appendChild(DIV(["class" => "navtop navside tab shm-toggler", "data-toggle-sel" => "#{$i}"], $block->header));
        }
        $html->appendChild(DIV(["class" => "navside tab".($hidable ? " blockbody" : "")], $block->body));
        return $html;
    }

    private function navlinks(Link $link, HTMLElement|string $desc, bool $active): HTMLElement
    {
        return A([
            "class" => $active ? "tab-selected" : "tab",
            "href" => $link->make_link(),
        ], $desc);
    }
}
