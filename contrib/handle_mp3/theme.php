<?php

class MP3FileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$data_href = get_base_href();
		$ilink = $image->get_image_link();
		$html = "
			<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'
			        codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0'
					width='400' height='170'>
				<param name='movie' value='$data_href/ext/handle_mp3/xspf_player.swf?song_url=$ilink'/>
				<param name='quality' value='high' />
				<embed src='$data_href/ext/handle_mp3/xspf_player.swf?song_url=$ilink' quality='high'
					pluginspage='http://www.macromedia.com/go/getflashplayer'
					width='400' height='170'
					type='application/x-shockwave-flash'></embed>
			</object>
			<p><a href='$ilink'>Download</a>";
		$page->add_block(new Block("Music", $html, "main", 0));
	}
}
?>
