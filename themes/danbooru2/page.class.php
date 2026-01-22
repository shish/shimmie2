<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, ARTICLE, BODY, DIV, FOOTER, H1, HEADER, IMG, LI, NAV, UL, emptyHTML};

use MicroHTML\HTMLElement;

/**
 * Name: Danbooru 2 Theme
 * Author: Bzchan <bzchan@animemahou.com>
 *         Updated by Daniel Oaks <daniel@danieloaks.net>
 *         Small changes added by zshall <http://seemslegit.com>
 * Description: This is a simple theme changing the css to make shimme
 *              look more like danbooru as well as adding a custom links
 *              bar and title to the top of every page.
 */

class Danbooru2Page extends Page
{
    protected function body_html(): HTMLElement
    {
        $left_block_html = [];
        $main_block_html = [];
        $sub_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "subheading":
                    $sub_block_html[] = $block->body;
                    break;
                case "main":
                    if ($block->header === "Posts") {
                        $block->header = "&nbsp;";
                    }
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        if ($this->subheading === "") {
            $subheading = null;
        } else {
            $subheading = DIV(["id" => "subtitle"], $this->subheading);
        }

        $site_name = Ctx::$config->get(SetupConfig::TITLE); // bzchan: change from normal default to get title for top of page
        $main_page = Ctx::$config->get(SetupConfig::MAIN_PAGE); // bzchan: change from normal default to get main page for top of page

        $custom_links = emptyHTML();
        foreach ($nav_links as $nav_link) {
            $custom_links->appendChild(LI($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)));
        }

        $custom_sublinks = "";
        if (count($sub_links) > 0) {
            $custom_sublinks = DIV(["class" => "sbar"]);
            foreach ($sub_links as $nav_link) {
                $custom_sublinks->appendChild(LI($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)));
            }
        }

        $title_link = H1(
            ["id" => "site-title"],
            IMG(["src" => "/favicon.ico", "alt" => "", "class" => "logo"]),
            A(["href" => make_link($main_page)], $site_name)
        );
        $flash_html = $this->flash_html();
        $footer_html = $this->footer_html();

        return BODY(
            $this->body_attrs(),
            HEADER(
                $title_link,
                UL(["id" => "navbar", "class" => "flat-list"], $custom_links),
                UL(["id" => "subnavbar", "class" => "flat-list"], $custom_sublinks),
            ),
            $subheading,
            emptyHTML(...$sub_block_html),
            NAV(...$left_block_html),
            ARTICLE(
                $flash_html,
                ...$main_block_html
            ),
            FOOTER(DIV($footer_html))
        );
    }

    private function navlinks(Url $link, HTMLElement|string $desc, bool $active): HTMLElement
    {
        return A([
            "class" => $active ? "current-page" : "tab",
            "href" => $link,
        ], $desc);
    }
}
