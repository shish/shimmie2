<?php

class CustomHomeTheme extends HomeTheme {
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
		<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:regular,bold,italic,thin,light,bolditalic,black,medium&amp;lang=en">
    	<link rel="stylesheet" href="//fonts.googleapis.com/icon?family=Material+Icons"  rel="stylesheet">
    	<link rel="stylesheet" href="{$base_href}/themes/{$theme_name}/material.min.css"  rel="stylesheet">
		$hh
		<script type="text/javascript" src="{$base_href}/themes/{$theme_name}/material.min.js"></script>
	</head>
	<body>
		$body
	</body>
</html>
EOD
);
	}

	public function build_body(/*string*/ $sitename, /*string*/ $main_links, /*string*/ $main_text, /*string*/ $contact_link, $num_comma, /*string*/ $counter_text) {
		$message_html = empty($main_text)     ? "" : "<div class='space' id='message'>$main_text</div>";
		$counter_html = empty($counter_text)  ? "" : "<div class='mdl-typography--text-center' id='counter'>$counter_text</div>";
		$contact_link = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a> -";
		$main_links_html = empty($main_links) ? "" : preg_replace('data-clink-sel="" ','', preg_replace('/shm-clink/', 'mdl-navigation__link', $main_links));
		$search_html = "
			<div class='mdl-grid'>
				<div class='mdl-layout-spacer'></div>
				<div class='mdl-cell mdl-cell--4-col'>
					<form class='mdl-typography--text-center' action='".make_link("post/list")."' method='GET'>
						<div class='mdl-textfield mdl-js-textfield'>
							<input id='search' name='search' size='30' type='search' value='' class='autocomplete_tags mdl-textfield__input' autocomplete='off' />
							<input type='hidden' name='q' value='/post/list'>
							<label class='mdl-textfield__label' for='search'>Search</label>
						</div>
					</form>
				</div>
				<div class='mdl-layout-spacer'></div>
			</div>
		";
		return "
		<div class='mdl-layout mdl-js-layout mdl-layout--fixed-drawer vertical-center'>
			<div class='mdl-layout__drawer'>
			    <span class='mdl-layout-title'>$sitename</span>
			    <nav class='mdl-navigation'>
			    	$main_links_html
		    	</nav>
			</div>
			<main class='mdl-layout__content'>
    			<div class='page-content'>
					<h2 class='mdl-typography--text-center'><a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a></h2>
					$search_html
					$message_html
					$counter_html
					<div class='mdl-typography--text-center' id='foot'>
						<p>$contact_link Serving $num_comma posts - Running <a href='http://code.shishnet.org/shimmie2/'>Shimmie</a>
					</div>
				</div>
			</main>
		</div>";
	}
}

