<?php

class FeaturedTheme extends Themelet {
	/**
	 * Show $text on the $page.
	 *
	 * @param Page $page
	 * @param Image $image
	 */
	public function display_featured(Page $page, Image $image) {
		$page->add_block(new Block("Featured Image", $this->build_featured_html($image), "left", 3));
	}

	/**
	 * @param int $image_id
	 * @return string
	 */
	public function get_buttons_html(/*int*/ $image_id) {
		global $user;
		return "
			".make_form(make_link("featured_image/set"))."
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='{$image_id}'>
			<input type='submit' value='Feature This'>
			</form>
		";
	}

	/**
	 * @param Image $image
	 * @param null|string $query
	 * @return string
	 */
	public function build_featured_html(Image $image, $query=null) {
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$tsize = get_thumbnail_size($image->width, $image->height);

		return "
			<a href='$h_view_link'>
				<img id='thumb_{$i_id}' title='{$h_tip}' alt='{$h_tip}' class='highlighted' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='{$h_thumb_link}'>
			</a>
		";
	}
}

