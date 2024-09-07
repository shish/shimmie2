<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{DIV, LI, A, rawHTML, emptyHTML, UL, ARTICLE, FOOTER, HR, HEADER, H1, NAV};

class FutabaPage extends Page
{
    public function body_html(): HTMLElement
    {
        $left_block_html = [];
        $main_block_html = [];
        $sub_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "main":
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                case "subheading":
                    $sub_block_html[] = $block->body;
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

        if ($this->left_enabled) {
            $left = NAV(...$left_block_html);
        } else {
            $left = null;
        }

        $flash_html = $this->flash_html();
        $footer_html = $this->footer_html();

        return emptyHTML(
            HEADER(
                H1($this->heading),
                $subheading,
                ...$sub_block_html
            ),
            $left,
            ARTICLE(
                $flash_html,
                ...$main_block_html
            ),
            FOOTER(
                HR(),
                $footer_html
            )
        );
    }
}
