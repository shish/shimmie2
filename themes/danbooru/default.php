<?php
/**
* Name: Danbooru Theme
* Author: Bzchan <bzchan@animemahou.com>
* Link: http://trac.shishnet.org/shimmie2/
* License: GPLv2
* Description: This is a simple theme changing the css to make shimme
*              look more like danbooru as well as adding a custom links
*              bar and title to the top of every page.
*/

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
Danbooru Theme - Notes (Bzchan)

Files: default.php, sidebar.js, style.css

How to use a theme
- Copy the danbooru folder with all its contained files into the "themes"
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
global $config;
$theme_name = $config->get_string('theme');
$base_href = $config->get_string('base_href');
$data_href = $config->get_string('data_href');
$contact_link = $config->get_string('contact_link');

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
		$i = str_replace(' ', '_', $h);
		if(!is_null($h)) $html .= "\n<h3>$h</h3>\n";
		if(!is_null($b)) $html .= "<div>$b</div>\n"; 
	}
	return $html;
}

$header_html = "";
foreach($this->headers as $line) {
	$header_html .= "\t\t$line\n";
}

$sideblock_html = "";
foreach($this->sideblocks as $block) {
	$sideblock_html .= block_to_html($block, true);
}

$mainblock_html = "";
foreach($this->mainblocks as $block) {
	$mainblock_html .= block_to_html($block, false);
}

$scripts = glob("scripts/*.js");
$script_html = "";
foreach($scripts as $script) {
	$script_html .= "\t\t<script src='$data_href/$script' type='text/javascript'></script>\n";
}

if($config->get_bool('debug_enabled')) {
	if(function_exists('memory_get_usage')) {
		$i_mem = sprintf("%5.2f", ((memory_get_usage()+512)/1024)/1024);
	}
	else {
		$i_mem = "???";
	}
	if(function_exists('getrusage')) {
		$ru = getrusage();
		$i_utime = sprintf("%5.2f", ($ru["ru_utime.tv_sec"]*1e6+$ru["ru_utime.tv_usec"])/1000000);
		$i_stime = sprintf("%5.2f", ($ru["ru_stime.tv_sec"]*1e6+$ru["ru_stime.tv_usec"])/1000000);
	}
	else {
		$i_utime = "???";
		$i_stime = "???";
	}
	$i_files = count(get_included_files());
	global $_execs;
	$debug = "<br>Took $i_utime + $i_stime seconds and {$i_mem}MB of RAM";
	$debug .= "; Used $i_files files and $_execs queries";
}
else {
	$debug = "";
}

global $config;
$version = $config->get_string('version');

$contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

if(empty($this->subheading)) {
	$subheading = "";
}
else {
	$subheading = "<div id='subtitle'>{$this->subheading}</div>";
}

$site_name = $config->get_string('title'); // bzchan: change from normal default to get title for top of page
$front_page = $config->get_string('front_page'); // bzchan: change from normal default to get front page for top of page

// bzchan: CUSTOM LINKS are prepared here, change these to whatever you like
$custom_links = "";
$custom_links .= "<li><a href='".make_link('user')."'>My Account</a></li>";
$custom_links .= "<li><a href='".make_link('index')."'>Posts</a></li>";
$custom_links .= "<li><a href='".make_link('comment/list')."'>Comments</a></li>";
$custom_links .= "<li><a href='".make_link('tags')."'>Tags</a></li>";


// bzchan: failed attempt to add heading after title_link (failure was it looked bad)
//if($this->heading==$site_name)$this->heading = '';
//$title_link = "<h1><a href='".make_link($front_page)."'>$site_name</a>/$this->heading</h1>";

// bzchan: prepare main title link
$title_link = "<h1><a href='".make_link($front_page)."'>$site_name</a></h1>";

print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$this->title}</title>
		<link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
$header_html
		<script src='$data_href/themes/$theme_name/sidebar.js' type='text/javascript'></script>
$script_html
	</head>

	<body>
		<div id="header">
		$title_link
		<ul class="flat-list">
			$custom_links
		</ul>
		</div>
		$subheading
		
		<div id="nav">$sideblock_html</div>
		<div id="body">$mainblock_html</div>

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
?>
