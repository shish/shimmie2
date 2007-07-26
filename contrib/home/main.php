<?php
/**
* Name: Home Extension
* Author: Bzchan <bzchan@animemahou.com>
* Link: http://trac.shishnet.org/shimmie2/
* License: GPLv2
* Description: Extension adds a page "home" containing user specified
*              links and a counter showing total number of posts. The
*              page is accessed via /home.
*/

class Home extends Extension {

	public function receive_event($event) {
		global $page;
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "home"))
		{
			// this is a request to display this page so output the page.
		  	$this->output_pages();
		}
		if(is_a($event, 'SetupBuildingEvent'))
		{
			$counters = array();
			foreach(glob("ext/home/counters/*") as $counter_dirname) {
				$name = str_replace("ext/home/counters/", "", $counter_dirname);
				$counters[ucfirst($name)] = $name;
			}
			
			$sb = new SetupBlock("Home Page");
			$sb->add_label("Page Links - Example: [$"."base/index|Posts]");
			$sb->add_longtext_option("home_links", "<br>");
			$sb->add_choice_option("home_counter", $counters, "<br>Counter: ");
			$sb->add_label("<br>Note: page accessed via /home");
			$event->panel->add_block($sb);
		}
	}

	private function get_body()
	{
		// returns just the contents of the body
		global $database;
		global $config;
		$base_href = $config->get_string('base_href');
		$data_href = $config->get_string('data_href');
		$sitename = $config->get_string('title');
	    $contact_link = $config->get_string('contact_link');
		$counter_dir = $config->get_string('home_counter', 'default');
		
		$total = ceil($database->db->GetOne("SELECT COUNT(*) FROM images"));
	   	   
		$numbers = array();
		$numbers = str_split($total);
		$num_comma = number_format($total);
	   
		$counter_text = "";
		foreach ($numbers as $cur)
		{
			$counter_text .= " <img alt='$cur' src='$data_href/ext/home/counters/$counter_dir/$cur.gif' />  ";
		}
		
		// get the homelinks and process them
		$main_links = $config->get_string('home_links');
		$main_links = str_replace('$base',	$base_href, 	$main_links);
		$main_links = str_replace('[', 		"<a href='", 	$main_links);
		$main_links = str_replace('|', 		"'>", 			$main_links);
		$main_links = str_replace(']', 		"</a>", 		$main_links);
				
		return "
		<div id='front-page'>
			<h1>
				<a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a>
			</h1>
			<div class='space' id='links'>
				$main_links
			</div>
			<div class='space'>
				<form action='".make_link()."' method='GET'>
				<input id='search_input' name='search' size='55' type='text' value='' autocomplete='off' /><br/>
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

    private function output_pages()
	{
		// output a sectionalised list of all the main pages on the site.
		global $config;
		$base_href = $config->get_string('base_href');
		$data_href = $config->get_string('data_href');
		$sitename = $config->get_string('title');
		$theme_name = $config->get_string('theme');
		
		$body = $this->get_body();	   
	  
	  	print <<<EOD
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
EOD;
		exit;
	}

}
add_event_listener(new Home());
?>
