<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, ARTICLE, BODY, DIV, FOOTER, HEADER, NAV, SECTION};

use MicroHTML\HTMLElement;

/**
 * Name: Lite Theme
 * Author: Zach Hall <zach@sosguy.net>
 * Link: http://seemslegit.com
 * Description: A mashup of Default, Danbooru, the interface on qwebirc, and
 * 	            some other sites, packaged in a light blue color.
 */

class LitePage extends Page
{
    protected function body_html(): HTMLElement
    {
        $nav = [];
        $left_block_html = [];
        $main_block_html = [];
        $sub_block_html  = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "main":
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                case "subheading":
                    $sub_block_html[] = $this->block_html($block, false);
                    break;
                case "nav":
                    $nav[] = $block->body;
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $flash_html = $this->flash_html();
        $footer_html = $this->footer_html();

        return BODY(
            $this->body_attrs(),
            HEADER(
                ... $nav,
                ...$sub_block_html
            ),
            NAV(...$left_block_html),
            ARTICLE(
                $flash_html,
                ...$main_block_html
            ),
            FOOTER($footer_html)
        );
    }

    protected function block_html(Block $block, bool $hidable = false): HTMLElement
    {
        $h = $block->header;
        $i = $block->id;
        if ($h === "Paginator") {
            return $block->body;
        }
        $html = SECTION(["id" => $i]);
        if (!is_null($block->header)) {
            $html->appendChild(DIV(["class" => "navtop navside tab shm-toggler", "data-toggle-sel" => "#{$i}"], $block->header));
        }
        $html->appendChild(DIV(["class" => "navside tab".($hidable ? " blockbody" : "")], $block->body));
        return $html;
    }
}
