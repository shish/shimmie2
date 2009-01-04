<?php

class HomeTheme extends Themelet {
	public function display_page($page, $sitename, $data_href, $theme_name, $body) {
		$page->set_mode("data");
		$page->set_data(<<<EOD
<html>
	<head>
		<title>$sitename</title>
		<link rel='stylesheet' href='$data_href/themes/$theme_name/style.css' type='text/css'>
	</head>
	<style>
		div#front-page h1 {font-size: 4em; margin-top: 2em; text-align: center; border: none; background: none;}
		div#front-page {text-align:center;}
		.space {margin-bottom: 1em;}
		div#front-page div#links a {margin: 0 0.5em;}
		div#front-page li {list-style-type: none; margin: 0;}
	</style>
	<body>
		$body
	</body>
</html>
EOD
);
	}

	public function build_body($sitename, $main_links, $contact_link, $num_comma, $counter_text) {
		return "
		<div id='front-page'>
			<h1>
				<a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a>
			</h1>
			<div class='space' id='links'>
				$main_links
			</div>
			<div class='space'>
				<form action='".make_link("post/list")."' method='GET'>
				<input id='search_input' name='search' size='55' type='text' value='' autocomplete='off' /><br/>
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Search'/>
				</form>
			</div>
      		<div style='font-size: 80%; margin-bottom: 2em;'>
		 		<a href='$contact_link'>contact</a> &ndash; Serving $num_comma posts
			</div>

			<div class='space'>
				Powered by <a href='http://trac.shishnet.org/shimmie2/'>Shimmie</a>
			</div>
			<div class='space'>
				$counter_text
			</div>
		</div>";
	}
}
?>
