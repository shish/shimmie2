<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{BODY, A, TABLE, TR, TD, SMALL, rawHTML, emptyHTML, DIV, ARTICLE, FOOTER, HEADER, H1, NAV};

class WarmPage extends Page
{
    protected function body_html(): HTMLElement
    {
        global $config;

        $site_name = $config->get_string(SetupConfig::TITLE);
        $data_href = get_base_href();
        $main_page = $config->get_string(SetupConfig::MAIN_PAGE);

        $left_block_html = [];
        $main_block_html = [];
        $head_block_html = [];
        $sub_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "head":
                    $head_block_html[] = TD(["style" => "width: 250px;"], SMALL($this->block_html($block, false)));
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

        $flash_html = $this->flash_html();
        $footer_html = $this->footer_html();

        return BODY(
            $this->body_attrs(),
            HEADER(
                DIV(
                    ["style" => "text-align: center;"],
                    H1(A(["href" => "$data_href/$main_page"], $site_name))
                    // Navigation links go here
                ),
                ...$head_block_html,
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
}
