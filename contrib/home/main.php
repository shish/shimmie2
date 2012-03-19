<?php
/*
* Name: Home Page
* Author: Bzchan <bzchan@animemahou.com>
* License: GPLv2
* Visibility: admin
* Description: Displays a front page with logo, search box and image count
* Documentation:
*  Once enabled, the page will show up at the URL "home", so if you want
*  this to be the front page of your site, you should go to "Board Config"
*  and set "Front Page" to "home".
*  <p>The images used for the numbers can be changed from the board config
*  page. If you want to use your own numbers, upload them into a new folder
*  in <code>/ext/home/counters</code>, and they'll become available
*  alongside the default choices.
*/

class Home extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("home_links", '[url=site://post/list]Posts[/url]
[site://comment/list]Comments[/url]
[site://tags]Tags[/url]
[site://ext_doc]>>[/url]');
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page;
		if($event->page_matches("home")) {
			$base_href = get_base_href();
			$sitename = $config->get_string('title');
			$theme_name = $config->get_string('theme');

			$body = $this->get_body();

			$this->theme->display_page($page, $sitename, $base_href, $theme_name, $body);
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$counters = array();
		foreach(glob("ext/home/counters/*") as $counter_dirname) {
			$name = str_replace("ext/home/counters/", "", $counter_dirname);
			$counters[ucfirst($name)] = $name;
		}

		$sb = new SetupBlock("Home Page");
		$sb->add_longtext_option("home_links", 'Page Links (Use BBCode, leave blank for defaults)');
		$sb->add_longtext_option("home_text", "<br>Page Text:<br>");
		$sb->add_choice_option("home_counter", $counters, "<br>Counter: ");
		$event->panel->add_block($sb);
	}


	private function get_body() {
		// returns just the contents of the body
		global $database;
		global $config;
		$base_href = get_base_href();
		$sitename = $config->get_string('title');
	    $contact_link = $config->get_string('contact_link');
		$counter_dir = $config->get_string('home_counter', 'default');

		$total = Image::count_images();
		$strtotal = "$total";
		$num_comma = number_format($total);

		$counter_text = "";
		$length = strlen($strtotal);
		for($n=0; $n<$length; $n++) {
			$cur = $strtotal[$n];
			$counter_text .= " <img alt='$cur' src='$base_href/ext/home/counters/$counter_dir/$cur.gif' />  ";
		}

		// get the homelinks and process them
		if(strlen($config->get_string('home_links', '')) > 0) {
			$main_links = $config->get_string('home_links');
		}
		else {
			$main_links = '[site://post/list]Posts[/url] [site://comment/list]Comments[/url] [site://tags]Tags[/url]';
			if(file_exists("ext/pools")) {$main_links .= ' [site://pools]Pools[/url]';}
			if(file_exists("ext/wiki")) {$main_links .= ' [site://wiki]Wiki[/url]';}
			$main_links .= ' [site://ext_doc]>>[/url]';
		}
		$main_links = format_text($main_links);
		$main_text = $config->get_string('home_text');

		return $this->theme->build_body($sitename, $main_links, $main_text, $contact_link, $num_comma, $counter_text);
	}
}
?>
