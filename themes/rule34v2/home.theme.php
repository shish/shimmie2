<?php

class CustomHomeTheme extends HomeTheme
{
    public function display_page(Page $page, string $sitename, string $base_href, string $theme_name, string $body): void
    {
        $page->set_mode("data");
        $page->add_auto_html_headers();
        $hh = $page->get_all_html_headers();
        $page->set_data(
            <<<EOD
<html lang="en">
	<head>
		<title>$sitename</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<meta name="theme-color" content="#7EB977">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		$hh
		<style>
			div#front-page h1 {font-size: 4em; margin-top: 2em; margin-bottom: 0; text-align: center; border: none; background: none; box-shadow: none; -webkit-box-shadow: none; -moz-box-shadow: none;}
			div#front-page {text-align:center;}
			.space {margin-bottom: 1em;}
			div#front-page div#links a {margin: 0 0.5em;}
			div#front-page li {list-style-type: none; margin: 0;}
			@media (max-width: 800px) {
				div#front-page h1 {font-size: 3em; margin-top: 0.5em; margin-bottom: 0.5em;}
				#counter {display: none;}
			}
		</style>
	</head>
	<body>
		$body
	</body>
</html>
EOD
        );
    }

    public function build_body(string $sitename, string $main_links, string $main_text, string $contact_link, $num_comma, string $counter_text): string
    {
        $main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
        $message_html = empty($main_text)     ? "" : "<div class='space' id='message'>$main_text</div>";
        $counter_html = empty($counter_text)  ? "" : "<div class='space' id='counter'>$counter_text</div>";
        $contact_link = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a> &ndash;";
        $search_html = "
			<div class='space' id='search'>
				<form action='".make_link("post/list")."' method='GET'>
				<input name='search' size='60' type='text' value='' class='autocomplete_tags' autofocus='autofocus'/>
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Search' style=\"border: 1px solid #888; height: 30px; border-radius: 2px; background: #EEE;\"/>
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

<!-- JuicyAds v3.1 -->
<script type='text/javascript' data-cfasync='false' async src='https://poweredby.jads.co/js/jads.js'></script>
<ins id='825625' data-width='908' data-height='270'></ins>
<script type='text/javascript' data-cfasync='false' async>(adsbyjuicy = window.adsbyjuicy || []).push({'adzone':825625});</script>
<!--JuicyAds END-->

				<small><small>
				$contact_link Serving $num_comma posts &ndash;
				Running <a href='http://code.shishnet.org/shimmie2/'>Shimmie2</a>
				</small></small>
			</div>
		</div>";
    }
}
