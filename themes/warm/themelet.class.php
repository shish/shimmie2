<?php
/**
 * A collection of common functions for theme parts
 */
class Themelet extends BaseThemelet {
	/**
	 * Generic thumbnail code; returns HTML rather than adding
	 * a block since thumbs tend to go inside blocks...
	 */
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_image_link = $image->get_image_link();
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());

		// If file is flash or svg then sets thumbnail to max size.
		if($image->ext == 'swf' || $image->ext == 'svg') {
			$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
		}
		else{
			$tsize = get_thumbnail_size($image->width, $image->height);
		}

		return "
			<div class='thumbblock'>
			<div class='rr thumb'>
				<div class='rrtop'><div></div></div>
				<div class='rrcontent'>
				<a href='$h_view_link' style='position: relative; display: block; height: {$tsize[1]}px; width: {$tsize[0]}px;'>
					<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='$h_thumb_link'>
				</a>
				</div>
				<div class='rrbot'><div></div></div>
			</div>
			</div>
		";
	}


	/**
	 * Put something in a box; specific to the default theme
	 */
	public function box($html) {
		return "
			<div class='rr'>
				<div class='rrtop'><div></div></div>
				<div class='rrcontent'>$html</div>
				<div class='rrbot'><div></div></div>
			</div>
		";
	}
}
?>
