<?php

declare(strict_types=1);

namespace Shimmie2;

class Page extends BasePage
{
    public function body_html(): string
    {
        global $config;

        $site_name = $config->get_string(SetupConfig::TITLE);
        $data_href = get_base_href();
        $main_page = $config->get_string(SetupConfig::MAIN_PAGE);

        $left_block_html = "";
        $main_block_html = "";
        $head_block_html = "";
        $sub_block_html = "";

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html .= $block->get_html(true);
                    break;
                case "head":
                    $head_block_html .= "<span style='width: 250px;'><small>".$block->get_html(false)."</small></span>";
                    break;
                case "main":
                    $main_block_html .= $block->get_html(false);
                    break;
                case "subheading":
                    $sub_block_html .= $block->body;
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";
        $footer_html = $this->footer_html();

        return <<<EOD
		<header>
			<div style="text-align: center;">
				<h1><a href="$data_href/$main_page">{$site_name}</a></h1>
				<!-- <p>[Navigation links go here] -->
			</div>
			$head_block_html
			$sub_block_html
		</header>
		<nav>
			$left_block_html
		</nav>
		<article>
			$flash_html
			$main_block_html
		</article>
		<footer>
		    $footer_html
		</footer>
EOD;
    }
}
