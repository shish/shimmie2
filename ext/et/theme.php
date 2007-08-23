<?php

class ETTheme extends Themelet {
	/*
	 * Create a page showing info
	 *
	 * $info = an array of ($name => $value)
	 */
	public function display_info_page($page, $info) {
		$page->set_title("System Info");
		$page->set_heading("System Info");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Data which is to be sent:", $this->build_data_form($info)));
	}
	
	protected function build_data_form($info) {
		$data = <<<EOD
Optional:
Add this site to the public shimmie users list: No
Site title: {$info['site_title']}
Theme: {$info['site_theme']}
Genre: {$info['site_genre']}
URL: {$info['site_url']}

System stats:
Shimmie: {$info['sys_shimmie']}
PHP: {$info['sys_php']}
OS: {$info['sys_os']}
Server: {$info['sys_server']}
Database: {$info['sys_db']}
Extensions: {$info['sys_extensions']}

Shimmie stats:
Images: {$info['stat_images']}
Comments: {$info['stat_comments']}
Users: {$info['stat_users']}
Tags: {$info['stat_tags']}
Applications: {$info['stat_image_tags']}
EOD;
		$html = <<<EOD
<form action='http://shimmie.shishnet.org/register.php' method='POST'>
	<input type='hidden' name='registration_api' value='1'>
	<textarea name='data' rows='20' cols='80'>$data</textarea>
	<br><input type='submit' value='Click to send to Shish'>
	<br>Your stats are useful so that I know which combinations
	of web servers / databases / etc I need to support,
	<br>and so that I can get some idea of how people use shimmie generally
</form>
EOD;
		return $html;
	}
}
?>
