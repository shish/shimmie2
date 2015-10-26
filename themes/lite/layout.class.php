<?php
/**
* Name: Lite Theme
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: A mashup of Default, Danbooru, the interface on qwebirc, and
* 	       some other sites, packaged in a light blue color.
*/
class Layout {

	/**
	 * turns the Page into HTML.
	 *
	 * @param Page $page
	 */
	public function display_page(Page $page) {
		global $config, $user;

		$theme_name = $config->get_string('theme', 'lite');
		$site_name = $config->get_string('title');
		$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');

		$header_html = "";
		ksort($page->html_headers);
		foreach($page->html_headers as $line) {
			$header_html .= "\t\t{$line}\n";
		}

		$menu = "<div class='menu'>
			<script type='text/javascript' src='{$data_href}/themes/{$theme_name}/wz_tooltip.js'></script>
			<a href='".make_link()."' onmouseover='Tip(&#39;Home&#39;, BGCOLOR, &#39;#C3D2E0&#39;, FADEIN, 100)' onmouseout='UnTip()'><img src='{$data_href}/favicon.ico' style='position: relative; top: 3px;'></a>
			<b>{$site_name}</b> ";
		
		// Custom links: These appear on the menu.
		$custom_links = "";
		if($user->is_anonymous()) {
			$custom_links .= $this->navlinks(make_link('user_admin/login'), "Account", array("user", "user_admin", "setup", "admin", "profile"));
		} else {
			$custom_links .= $this->navlinks(make_link('user'), "Account", array("user", "setup", "user_admin", "admin", "profile"));
		}
		$custom_links .= $this->navlinks(make_link('post/list'), "Posts", array("post", "view"));
		$custom_links .= $this->navlinks(make_link('comment/list'), "Comments", array("comment"));
		$custom_links .= $this->navlinks(make_link('tags'), "Tags", array("tags"));
		if(class_exists("Pools")) {
			$custom_links .= $this->navlinks(make_link('pool/list'), "Pools", array("pool"));
		}
		$custom_links .= $this->navlinks(make_link('upload'), "Upload", array("upload"));
		if(class_exists("Wiki")) {
			$custom_links .= $this->navlinks(make_link('wiki/rules'), "Rules", array("wiki/rules"));
			$custom_links .= $this->navlinks(make_link('wiki'), "Wiki", array("wiki"));
		}
		$menu .= "{$custom_links}</div>";
		
		$left_block_html = "";
		$main_block_html = "";
		$sub_block_html  = "";
		$user_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
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

		$custom_sublinks = "<div class='sbar'>";
		// hack
		$username = url_escape($user->name);
		// hack
		$qp = explode("/", ltrim(_get_query(), "/"));
		$cs = "";

		// php sucks
		switch($qp[0]) {
			default:
				$cs = $user_block_html;
				break;
			case "":
				# FIXME: this assumes that the front page is
				# post/list; in 99% of case it will either be
				# post/list or home, and in the latter case
				# the subnav links aren't shown, but it would
				# be nice to be correct
			case "post":
				if(class_exists("NumericScore")){
					$cs .= "<b>Popular by </b><a href='".make_link('popular_by_day')."'>Day</a><b>/</b><a href='".make_link('popular_by_month')."'>Month</a><b>/</b><a href='".make_link('popular_by_year')."'>Year</a> ";
				}
				$cs .= "<a class='tab' href='".make_link('post/list')."'>All</a>";
				if(class_exists("Favorites")){ $cs .= "<a class='tab' href='".make_link("post/list/favorited_by={$username}/1")."'>My Favorites</a>";}
				if(class_exists("RSS_Images")){ $cs .= "<a class='tab' href='".make_link('rss/images')."'>Feed</a>";}
				if(class_exists("Random_Image")){ $cs .= "<a class='tab' href='".make_link("random_image/view")."'>Random Image</a>";}
				if(class_exists("Wiki")){ $cs .= "<a class='tab' href='".make_link("wiki/posts")."'>Help</a>";
				}else{ $cs .= "<a class='tab' href='".make_link("ext_doc/index")."'>Help</a>";}
				break;
			case "comment":
				$cs .= "<a class='tab' href='".make_link('comment/list')."'>All</a>";
				$cs .= "<a class='tab' href='".make_link('rss/comments')."'>Feed</a>";
				$cs .= "<a class='tab' href='".make_link("ext_doc/comment")."'>Help</a>";
				break;
			case "pool":
				$cs .= "<a class='tab' href='".make_link('pool/list')."'>List</a>";
				$cs .= "<a class='tab' href='".make_link("pool/new")."'>Create</a>";
				$cs .= "<a class='tab' href='".make_link("pool/updated")."'>Changes</a>";
				$cs .= "<a class='tab' href='".make_link("ext_doc/pools")."'>Help</a>";
				break;
			case "wiki":
				$cs .= "<a class='tab' href='".make_link('wiki')."'>Index</a>";
				$cs .= "<a class='tab' href='".make_link("wiki/rules")."'>Rules</a>";
				$cs .= "<a class='tab' href='".make_link("ext_doc/wiki")."'>Help</a>";
				break;
			case "tags":
			case "alias":
				$cs .= "<a class='tab' href='".make_link('tags/map')."'>Map</a>";
				$cs .= "<a class='tab' href='".make_link('tags/alphabetic')."'>Alphabetic</a>";
				$cs .= "<a class='tab' href='".make_link('tags/popularity')."'>Popularity</a>";
				$cs .= "<a class='tab' href='".make_link('tags/categories')."'>Categories</a>";
				$cs .= "<a class='tab' href='".make_link('alias/list')."'>Aliases</a>";
				$cs .= "<a class='tab' href='".make_link("ext_doc/tag_edit")."'>Help</a>";
				break;
			case "upload":
				if(class_exists("Wiki")) { $cs .= "<a class='tab' href='".make_link("wiki/upload_guidelines")."'>Guidelines</a>"; }
				break;
			case "random":
				$cs .= "<a class='tab' href='".make_link('random/view')."'>Shuffle</a>";
				$cs .= "<a class='tab' href='".make_link('random/download')."'>Download</a>";
				break;
			case "featured":
				$cs .= "<a class='tab' href='".make_link('featured/download')."'>Download</a>";
				break;
		}

		if($cs == "") {
			$custom_sublinks = "";
		} else {
			$custom_sublinks .= "$cs</div>";
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='mailto:{$contact_link}'>Contact</a>";
		//$subheading = empty($page->subheading) ? "" : "<div id='subtitle'>{$page->subheading}</div>";

		/*$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}*/
		if($page->left_enabled == false) {
			$left_block_html = "";
			$main_block_html = "<article id='body_noleft'>{$main_block_html}</article>";
		} else {
			$left_block_html = "<nav>{$left_block_html}</nav>";
			$main_block_html = "<article>{$main_block_html}</article>";
		}

		$flash = $page->get_cookie("flash_message");
		$flash_html = "";
		if($flash) {
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
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/graphs/contributors">The Team</a>
			2007-2014,
			based on the Danbooru concept.<br />
			Lite Theme by <a href="http://seemslegit.com">Zach</a>
			$debug
			$contact
		</footer>
	</body>
</html>
EOD;
	} /* end of function display_page() */


	/**
	 * A handy function which does exactly what it says in the method name.
	 *
	 * @param Block $block
	 * @param bool $hidable
	 * @param string $salt
	 * @return string
	 */
	public function block_to_html(Block $block, $hidable=false, $salt="") {
		$h = $block->header;
		$b = $block->body;
		$i = str_replace(' ', '_', $h) . $salt;
		$html = "<section id='{$i}'>";
		if(!is_null($h)) {
			if($salt == "main") {
				$html .= "<div class='maintop navside tab shm-toggler' data-toggle-sel='#{$i}'>{$h}</div>";
			} else {
				$html .= "<div class='navtop navside tab shm-toggler' data-toggle-sel='#{$i}'>{$h}</div>";
			}
		}
		if(!is_null($b)) {
			if($salt =="main") {
				$html .= "<div class='blockbody'>{$b}</div>";
			}
			else {
				$html .= "
					<div class='navside tab'>{$b}</div>
				";
			}
		}
		$html .= "</section>";
		return $html;
	}

	/**
	 * @param string $link
	 * @param null|string $desc
	 * @param array $pages_matched
	 * @return null|string
	 */
	public function navlinks($link, $desc, $pages_matched) {
		/**
		 * Woo! We can actually SEE THE CURRENT PAGE!! (well... see it highlighted in the menu.)
		 */
		$html = null;
		$url = ltrim(_get_query(), "/");

		$re1='.*?';
		$re2='((?:[a-z][a-z_]+))';

		if (preg_match_all ("/".$re1.$re2."/is", $url, $matches)) {
			$url=$matches[1][0];
		}

		$count_pages_matched = count($pages_matched);

		for($i=0; $i < $count_pages_matched; $i++) {
			if($url == $pages_matched[$i]) {
				$html = "<a class='tab-selected' href='{$link}'>{$desc}</a>";
			}
		}

		if(is_null($html)) {$html = "<a class='tab' href='{$link}'>{$desc}</a>";}

		return $html;
	}

} /* end of class Layout */
