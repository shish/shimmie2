<?
global $config;
$base_href = $config->get_string('base_href');
$data_href = $config->get_string('data_href');
$contact_link = $config->get_string('contact_link');

function block_to_html($block) {
	$h = $block->header;
	$b = $block->body;
	$i = str_replace(' ', '_', $h);
	$html = "";
	if(!is_null($h)) $html .= "\n<h3 id='$i-toggle' onclick=\"toggle('$i')\">$h</h3>\n";
	if(!is_null($b)) $html .= "<div id='$i'>$b</div>\n";
	return $html;
}

$sideblock_html = "";
foreach($this->sideblocks as $block) {
	$sideblock_html .= block_to_html($block);
}

$mainblock_html = "";
foreach($this->mainblocks as $block) {
	$mainblock_html .= block_to_html($block);
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

print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$this->title}</title>
		<link rel="stylesheet" href="$data_href/themes/default/style.css" type="text/css">
		<script src='$data_href/themes/default/sidebar.js' type='text/javascript'></script>
$script_html
	</head>

	<body>
		<h1>{$this->heading}</h1>
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
