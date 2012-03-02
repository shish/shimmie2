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
		$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');

		$header_html = "";
		ksort($page->html_headers);
		foreach($page->html_headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$main_block_html = "";
		$sub_block_html  = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true, "left");
					break;
				case "main":
					$main_block_html .= $this->block_to_html($block, false, "main");
					break;
				case "subheading":
					$sub_block_html .= $this->block_to_html($block, false, "main");
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}

		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
$header_html
	</head>

	<body>
		<h1$wrapper>{$page->heading}</h1>

		$sub_block_html

		<div id="nav">$left_block_html</div>
		<div id="body">$main_block_html</div>

		<div id="footer">
			Images &copy; their respective owners,
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/contributors">The Team</a>
			2007-2012,
			based on the Danbooru concept.
			$debug
			$contact
		</div>
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
		$html = "";
		$i = str_replace(' ', '_', $h) . $salt;
		if(!is_null($h)) $html .= "
			<h3 data-toggle-id='$i' class='hrr shm-toggler'>$h</h3>
		";
		if(!is_null($b)) {
			if(strpos($b, "<!-- cancel border -->") === FALSE) {
				$html .= "<div class='blockbody brr' id='$i'>$b</div>";
			}
			else {
				$html .= "<div class='blockbody' id='$i'>$b</div>";
			}
		}

		return $html;
	}
}
?>
