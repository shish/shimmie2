<?php
/**
* Name: Lite Theme
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: A mashup of Default, Danbooru, the interface on qwebirc, and
* 	       some other sites, packaged in a light blue color.
*/
class Layout
{
    public function display_page(Page $page, array $nav_links, array $sub_links)
    {
        global $config;

        $theme_name = $config->get_string(SetupConfig::THEME, 'lite');
        $site_name = $config->get_string(SetupConfig::TITLE);
        $data_href = get_base_href();
        $contact_link = contact_link();
        $header_html = $page->get_all_html_headers();

        $menu = "<div class='menu'>
			<script type='text/javascript' src='{$data_href}/themes/{$theme_name}/wz_tooltip.js'></script>
			<a href='".make_link()."' onmouseover='Tip(&#39;Home&#39;, BGCOLOR, &#39;#C3D2E0&#39;, FADEIN, 100)' onmouseout='UnTip()'><img src='{$data_href}/favicon.ico' style='position: relative; top: 3px;'></a>
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

        foreach ($page->blocks as $block) {
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

        $debug = get_debug_info();

        $contact = empty($contact_link) ? "" : "<br><a href='{$contact_link}'>Contact</a>";
        //$subheading = empty($page->subheading) ? "" : "<div id='subtitle'>{$page->subheading}</div>";

        /*$wrapper = "";
        if(strlen($page->heading) > 100) {
            $wrapper = ' style="height: 3em; overflow: auto;"';
        }*/
        if ($page->left_enabled == false) {
            $left_block_html = "";
            $main_block_html = "<article id='body_noleft'>{$main_block_html}</article>";
        } else {
            $left_block_html = "<nav>{$left_block_html}</nav>";
            $main_block_html = "<article>{$main_block_html}</article>";
        }

        $flash = $page->get_cookie("flash_message");
        $flash_html = "";
        if (!empty($flash)) {
            $flash_html = "<b id='flash'>".nl2br(html_escape($flash))." <a href='#' onclick=\"\$('#flash').hide(); return false;\">[X]</a></b>";
        }

        print <<<EOD
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
	<head>
		<title>{$page->title}</title>
		$header_html
	</head>

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
			Images &copy; their respective owners,
			<a href="https://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="https://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/graphs/contributors">The Team</a>
			2007-2019,
			based on the Danbooru concept.<br />
			Lite Theme by <a href="http://seemslegit.com">Zach</a>
			$debug
			$contact
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

    /**
     * #param string[] $pages_matched
     */
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
} /* end of class Layout */
