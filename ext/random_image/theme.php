<?php

class RandomImageTheme extends Themelet
{
	/**
	 * @param Page $page
	 * @param Image $image
	 */
	public function display_random(Page $page, Image $image)
	{
		$page->add_block(new Block("Random Image", $this->build_random_html($image), "left", 8));
	}

	/**
	 * @param Image $image
	 * @param null|string $query
	 * @return string
	 */
	public function build_random_html(Image $image, $query = null)
	{

		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$tsize = get_thumbnail_size($image->width, $image->height);

		return "
				<center><div>

					<a href='$h_view_link' style='position: relative; height: {$tsize[1]}px; width: {$tsize[0]}px;'>
						<img id='thumb_rand_$i_id' title='$h_tip' alt='$h_tip' class='highlighted' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='$h_thumb_link'>
					</a>

				</div></center>
			";
	}
}

