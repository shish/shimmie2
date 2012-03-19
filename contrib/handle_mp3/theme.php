<?php

class MP3FileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$data_href = get_base_href();
		$ilink = $image->get_image_link();
		$fname = url_escape($image->filename); //Most of the time this will be the title/artist of the song.
		$html = "
			<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'
			        codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0'
					width='400' height='15'>
				<param name='movie' value='$data_href/ext/handle_mp3/xspf_player_slim.swf?song_url=$ilink'/>
				<param name='quality' value='high' />
				<embed src='$data_href/ext/handle_mp3/xspf_player_slim.swf?song_url=$ilink&song_title=$fname' quality='high'
					pluginspage='http://www.macromedia.com/go/getflashplayer'
					width='400' height='15'
					type='application/x-shockwave-flash'></embed>
			</object>
			<p><a href='$ilink'>Download</a>";
		$page->add_block(new Block("Music", $html, "main", 10));
	}
}
?>
