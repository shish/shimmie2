<?php

class Layout {
	function display_page($page) {
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
		$sub_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true, "left");
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
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
$header_html
		<script src='$data_href/themes/$theme_name/script.js' type='text/javascript'></script>
	</head>

	<body>
		<h1$wrapper>{$page->heading}</h1>
		$subheading
		$sub_block_html
		
		<div id="nav">$left_block_html</div>
		<div id="body">$main_block_html</div>

		<div id="footer">
			<hr>
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

	function block_to_html($block, $hidable=false, $salt="") {
		$h = $block->header;
		$b = $block->body;
		$html = "";
		$i = str_replace(' ', '_', $h) . $salt;
		if(!is_null($h)) $html .= "\n<h3 data-toggle-id='$i' class='shm-toggler'>$h</h3>\n";
		if(!is_null($b)) $html .= "<div id='$i'>$b</div>\n";
		return $html;
	}
}
?>
