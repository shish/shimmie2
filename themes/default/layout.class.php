<?php

class Layout {
	function display_page($page) {
		global $config;
		$theme_name = $config->get_string('theme');
		$base_href = $config->get_string('base_href');
		$data_href = $config->get_string('data_href');
		$contact_link = $config->get_string('contact_link');
		$version = $config->get_string('version');

		$header_html = "";
		foreach($page->headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$main_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true);
					break;
				case "main":
					$main_block_html .= $this->block_to_html($block, false);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

		if(empty($this->subheading)) {
			$subheading = "";
		}
		else {
			$subheading = "<div id='subtitle'>{$this->subheading}</div>";
		}

		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
		<link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
$header_html
		<script src='$data_href/themes/$theme_name/sidebar.js' type='text/javascript'></script>
	</head>

	<body>
		<h1>{$page->heading}</h1>
		$subheading
		
		<div id="nav">$left_block_html</div>
		<div id="body">$main_block_html</div>

		<div id="footer">
			<hr>
			Images &copy; their respective owners,
			<a href="http://trac.shishnet.org/shimmie2/">$version</a> &copy; 
			<a href="http://www.shishnet.org/">Shish</a> 2007,
			based on the <a href="http://trac.shishnet.org/shimmie2/wiki/DanbooruRipoff">Danbooru</a> concept.
			$debug
			$contact
		</div>
	</body>
</html>
EOD;
	}

	function block_to_html($block, $hidable=false) {
		$h = $block->header;
		$b = $block->body;
		$html = "";
		if($hidable) {
			$i = str_replace(' ', '_', $h);
			if(!is_null($h)) $html .= "\n<h3 id='$i-toggle' onclick=\"toggle('$i')\">$h</h3>\n";
			if(!is_null($b)) $html .= "<div id='$i'>$b</div>\n";
		}
		else {
			if(!is_null($h)) $html .= "\n<h3>$h</h3>\n";
			if(!is_null($b)) $html .= "<div>$b</div>\n";
		}
		return $html;
	}
}
?>
