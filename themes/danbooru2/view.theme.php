<?php

class CustomViewImageTheme extends ViewImageTheme {
	/**
	 * @param Image $image
	 * @param $editor_parts
	 */
	public function display_page(Image $image, $editor_parts) {
		global $page;
		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block("Search", $this->build_navigation($image), "left", 0));
		$page->add_block(new Block("Information", $this->build_information($image), "left", 15));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 15));
	}

	/**
	 * @param Image $image
	 * @return string
	 */
	private function build_information(Image $image) {
		$h_owner = html_escape($image->get_owner()->name);
		$h_ownerlink = "<a href='".make_link("user/$h_owner")."'>$h_owner</a>";
		$h_ip = html_escape($image->owner_ip);
		$h_date = autodate($image->posted);
		$h_filesize = to_shorthand_int($image->filesize);

		global $user;
		if($user->can("view_ip")) {
			$h_ownerlink .= " ($h_ip)";
		}

		$html = "
		ID: {$image->id}
		<br>Uploader: $h_ownerlink
		<br>Date: $h_date
		<br>Size: $h_filesize ({$image->width}x{$image->height})
		";

		if(!is_null($image->source)) {
			$h_source = html_escape($image->source);
			if(substr($image->source, 0, 7) != "http://" && substr($image->source, 0, 8) != "https://") {
				$h_source = "http://" . $h_source;
			}
			$html .= "<br>Source: <a href='$h_source'>link</a>";
		}

		if(ext_is_live("Ratings")) {
			if($image->rating == null || $image->rating == "u"){
				$image->rating = "u";
			}
			if(ext_is_live("Ratings")) {
				$h_rating = Ratings::rating_to_human($image->rating);
				$html .= "<br>Rating: $h_rating";
			}
		}

		return $html;
	}

	/**
	 * @param Image $image
	 * @return string
	 */
	protected function build_navigation(Image $image) {
		//$h_pin = $this->build_pin($image);
		$h_search = "
			<form action='".make_link()."' method='GET'>
				<input name='search' type='text'  style='width:75%'>
				<input type='submit' value='Go' style='width:20%'>
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
		";

		return "$h_search";
	}
}

