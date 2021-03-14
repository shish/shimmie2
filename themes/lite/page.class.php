<?php declare(strict_types=1);
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
    public bool $left_enabled = true;

    public function disable_left()
    {
        $this->left_enabled = false;
    }

    public function render()
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
                    $left_block_html .= $this->block_to_html($block, true, "left");
                    break;
                case "main":
                    $main_block_html .= $this->block_to_html($block, false, "main");
                    break;
                case "user":
                    $user_block_html .= $block->body;
                    break;
                case "subheading":
                    $sub_block_html .= $this->block_to_html($block, false, "main");
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

        if ($this->left_enabled == false) {
            $left_block_html = "";
            $main_block_html = "<article id='body_noleft'>{$main_block_html}</article>";
        } else {
            $left_block_html = "<nav>{$left_block_html}</nav>";
            $main_block_html = "<article>{$main_block_html}</article>";
        }

        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";
        $head_html = $this->head_html();
        $footer_html = $this->footer_html();

        print <<<EOD
<!doctype html>
<html class="no-js" lang="en">
    $head_html
	<body>
		<header>
			$menu
			$custom_sublinks
			$sub_block_html
		</header>
		$left_block_html
		$flash_html
		$main_block_html
		<footer>
		    $footer_html
		</footer>
	</body>
</html>
EOD;
    } /* end of function display_page() */

    public function block_to_html(Block $block, bool $hidable=false, string $salt=""): string
    {
        $h = $block->header;
        $b = $block->body;
        $i = str_replace(' ', '_', $h) . $salt;
        $html = "<section id='{$i}'>";
        if (!is_null($h)) {
            if ($salt == "main") {
                $html .= "<div class='maintop navside tab shm-toggler' data-toggle-sel='#{$i}'>{$h}</div>";
            } else {
                $html .= "<div class='navtop navside tab shm-toggler' data-toggle-sel='#{$i}'>{$h}</div>";
            }
        }
        if (!is_null($b)) {
            if ($salt =="main") {
                $html .= "<div class='blockbody'>{$b}</div>";
            } else {
                $html .= "
					<div class='navside tab'>{$b}</div>
				";
            }
        }
        $html .= "</section>";
        return $html;
    }

    public function navlinks(Link $link, string $desc, bool $active): ?string
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
