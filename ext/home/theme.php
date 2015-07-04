<?php

class HomeTheme extends Themelet {
	public function display_page(Page $page, $sitename, $base_href, $theme_name, $body) {
		$page->set_mode("data");
		$hh = "";
		$page->add_auto_html_headers();
		foreach($page->html_headers as $h) {$hh .= $h;}
		$page->set_data(<<<EOD
<html>
	<head>
		<title>$sitename</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		$hh
	</head>
	<style>
		div#front-page h1 {font-size: 4em; margin-top: 2em; margin-bottom: 0px; text-align: center; border: none; background: none; box-shadow: none; -webkit-box-shadow: none; -moz-box-shadow: none;}
		div#front-page {text-align:center;}
		.space {margin-bottom: 1em;}
		div#front-page div#links a {margin: 0 0.5em;}
		div#front-page li {list-style-type: none; margin: 0;}
		@media (max-width: 800px) {
			div#front-page h1 {font-size: 3em; margin-top: 0.5em; margin-bottom: 0.5em;}
			#counter {display: none;}
		}
	</style>
	<body>
		$body
	</body>
</html>
EOD
);
	}

	public function build_body(/*string*/ $sitename, /*string*/ $main_links, /*string*/ $main_text, /*string*/ $contact_link, $num_comma, /*string*/ $counter_text) {
		$main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
		$message_html = empty($main_text)     ? "" : "<div class='space' id='message'>$main_text</div>";
		$counter_html = empty($counter_text)  ? "" : "<div class='space' id='counter'>$counter_text</div>";
		$contact_link = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a> &ndash;";
		$search_html = "
			<div class='space' id='search'>
				<form action='".make_link("post/list")."' method='GET'>
				<input name='search' size='30' type='search' value='' class='autocomplete_tags' autofocus='autofocus' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Search'/>
				</form>
			</div>
		";
		return "
		<div id='front-page'>
			<h1><a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a></h1>
			$main_links_html
			$search_html
			$message_html
			$counter_html
			<div class='space' id='foot'>
				<small><small>
				$contact_link Serving $num_comma posts &ndash;
				Running <a href='http://code.shishnet.org/shimmie2/'>Shimmie</a>
				</small></small>
			</div>
		</div>";
	}
}

