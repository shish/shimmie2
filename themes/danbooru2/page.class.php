<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{BODY, DIV, LI, A, IMG, rawHTML, emptyHTML, UL, ARTICLE, FOOTER, EM, HEADER, H1, NAV};

/**
 * Name: Danbooru 2 Theme
 * Author: Bzchan <bzchan@animemahou.com>, updated by Daniel Oaks <daniel@danieloaks.net>
 * Link: https://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: This is a simple theme changing the css to make shimme
 *              look more like danbooru as well as adding a custom links
 *              bar and title to the top of every page.
 */
//Small changes added by zshall <http://seemslegit.com>
//Changed CSS and layout to make shimmie look even more like danbooru
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
Danbooru 2 Theme - Notes (Bzchan)

Files: default.php, style.css

How to use a theme
- Copy the danbooru2 folder with all its contained files into the "themes"
  directory in your shimmie installation.
- Log into your shimmie and change the Theme in the Board Config to your
  desired theme.

Changes in this theme include
- Adding and editing various elements in the style.css file.
- $site_name and $front_name retreival from config added.
- $custom_link and $title_link preparation just before html is outputed.
- Altered outputed html to include the custom links and removed heading
  from being displayed (subheading is still displayed)
- Note that only the sidebar has been left aligned. Could not properly
  left align the main block because blocks without headers currently do
  not have ids on there div elements. (this was a problem because
  paginator block must be centered and everything else left aligned)

Tips
- You can change custom links to point to whatever pages you want as well as adding
  more custom links.
- The main title link points to the Front Page set in your Board Config options.
- The text of the main title is the Title set in your Board Config options.
- Themes make no changes to your database or main code files so you can switch
  back and forward to other themes all you like.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Danbooru2Page extends Page
{
    protected function body_html(): HTMLElement
    {
        global $config;

        list($nav_links, $sub_links) = $this->get_nav_links();

        $left_block_html = [];
        $user_block_html = [];
        $main_block_html = [];
        $sub_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "user":
                    $user_block_html[] = $block->body;
                    break;
                case "subheading":
                    $sub_block_html[] = $block->body;
                    break;
                case "main":
                    if ($block->header == "Posts") {
                        $block->header = "&nbsp;";
                    }
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        if (empty($this->subheading)) {
            $subheading = null;
        } else {
            $subheading = DIV(["id" => "subtitle"], $this->subheading);
        }

        $site_name = $config->get_string(SetupConfig::TITLE); // bzchan: change from normal default to get title for top of page
        $main_page = $config->get_string(SetupConfig::MAIN_PAGE); // bzchan: change from normal default to get main page for top of page

        $custom_links = emptyHTML();
        foreach ($nav_links as $nav_link) {
            $custom_links->appendChild(LI($this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)));
        }

        $custom_sublinks = "";
        if (!empty($sub_links)) {
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

    private function navlinks(Link $link, HTMLElement|string $desc, bool $active): HTMLElement
    {
        return A([
            "class" => $active ? "current-page" : "tab",
            "href" => $link->make_link(),
        ], $desc);
    }
}
