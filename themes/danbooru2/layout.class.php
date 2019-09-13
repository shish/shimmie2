<?php
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

class Layout
{
    public function display_page($page, array $nav_links, array $sub_links)
    {
        global $config, $user;

        //$theme_name = $config->get_string(SetupConfig::THEME);
        //$base_href = $config->get_string('base_href');
        //$data_href = get_base_href();
        $contact_link = contact_link();
        $header_html = $page->get_all_html_headers();

        $left_block_html = "";
        $user_block_html = "";
        $main_block_html = "";
        $sub_block_html = "";

        foreach ($page->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html .= $block->get_html(true);
                    break;
                case "user":
                    $user_block_html .= $block->body; // $this->block_to_html($block, true);
                    break;
                case "subheading":
                    $sub_block_html .= $block->body; // $this->block_to_html($block, true);
                    break;
                case "main":
                    if ($block->header == "Images") {
                        $block->header = "&nbsp;";
                    }
                    $main_block_html .= $block->get_html(false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $debug = get_debug_info();

        $contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

        if (empty($this->subheading)) {
            $subheading = "";
        } else {
            $subheading = "<div id='subtitle'>{$this->subheading}</div>";
        }

        $site_name = $config->get_string(SetupConfig::TITLE); // bzchan: change from normal default to get title for top of page
        $main_page = $config->get_string(SetupConfig::MAIN_PAGE); // bzchan: change from normal default to get main page for top of page

        $custom_links = "";
        foreach ($nav_links as $nav_link) {
            $custom_links .=  "<li>".$this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)."</li>";
        }

        $custom_sublinks = "";
        if(!empty($sub_links)) {
            $custom_sublinks = "<div class='sbar'>";
            foreach ($sub_links as $nav_link) {
                $custom_sublinks .= "<li>".$this->navlinks($nav_link->link, $nav_link->description, $nav_link->active)."</li>";
            }
            $custom_sublinks .= "</div>";
        }


        // bzchan: failed attempt to add heading after title_link (failure was it looked bad)
        //if($this->heading==$site_name)$this->heading = '';
        //$title_link = "<h1><a href='".make_link($main_page)."'>$site_name</a>/$this->heading</h1>";

        // bzchan: prepare main title link
        $title_link = "<h1 id='site-title'><a href='".make_link($main_page)."'>$site_name</a></h1>";

        if ($page->left_enabled) {
            $left = "<nav>$left_block_html</nav>";
            $withleft = "withleft";
        } else {
            $left = "";
            $withleft = "noleft";
        }

        $flash = $page->get_cookie("flash_message");
        $flash_html = "";
        if ($flash) {
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
			$title_link
			<ul id="navbar" class="flat-list">
				$custom_links
			</ul>
			<ul id="subnavbar" class="flat-list">
				$custom_sublinks
			</ul>
		</header>
		$subheading
		$sub_block_html
		$left
		<article class="$withleft">
			$flash_html
			$main_block_html
		</article>
		<footer><div>
			Running Shimmie &ndash;
			Images &copy; their respective owners,
			<a href="https://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="https://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/graphs/contributors">The Team</a>
			2007-2019,
			based on the Danbooru concept<br />
			$debug
			$contact
		</div></footer>
	</body>
</html>
EOD;
    }

    public function navlinks(Link $link, string $desc, bool $active): ?string
    {
        $html = null;
        if ($active) {
            $html = "<a class='current-page' href='{$link->make_link()}'>{$desc}</a>";
        } else {
            $html = "<a class='tab' href='{$link->make_link()}'>{$desc}</a>";
        }

        return $html;
    }
}
