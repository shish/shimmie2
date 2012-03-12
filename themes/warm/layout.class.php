<?php
/**
 * A class to turn a Page data structure into a blob of HTML
 */
class Layout {
	/**
	 * turns the Page into HTML
	 */
	public function display_page(Page $page) {
		global $config;

		$theme_name = $config->get_string('theme', 'default');
		$site_name = $config->get_string('title');
		$data_href = get_base_href();
		$main_page = $config->get_string('main_page');
		$contact_link = $config->get_string('contact_link');

		$header_html = "";
		foreach($page->html_headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$main_block_html = "";
		$head_block_html = "";
		$sub_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true, "left");
					break;
				case "head":
					$head_block_html .= "<td width='250'><small>".$this->block_to_html($block, false, "head")."</small></td>";
					break;
				case "main":
					$main_block_html .= $this->block_to_html($block, false, "main");
					break;
				case "subheading":
					$sub_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a>";
		$subheading = empty($page->subheading) ? "" : "<div id='subtitle'>{$page->subheading}</div>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
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
			<table id="header" class="bgtop" width="100%" height="113px">
				<tr>
					<td><center>
						<h1><a href="$data_href/$main_page">{$site_name}</a></h1>
						<p>[Navigation links go here]
					</center></td>
					$head_block_html
				</tr>
			</table>
			$sub_block_html
		</header>
		<nav>
			$left_block_html
		</nav>
		<article>
			$main_block_html
		</article>
		<footer>
			Images &copy; their respective owners,
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/contributors">The Team</a>
			2007-2012,
			based on the Danbooru concept.
			$debug
			$contact
		</footer>
	</body>
</html>
EOD;
	}

	/**
	 * A handy function which does exactly what it says in the method name
	 */
	private function block_to_html($block, $hidable=false, $salt="") {
		$h = $block->header;
		$b = $block->body;
		$i = str_replace(' ', '_', $h) . $salt;
		$html = "<section id='$i'>";
		if(!is_null($h)) $html .= "<h3 data-toggle-sel='#$i' class='shm-toggler'>$h</h3>";
		if(!is_null($b)) $html .= "<div class='blockbody'>$b</div>";
		$html .= "</section>";
		return $html;
	}
}
?>
