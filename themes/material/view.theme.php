<?php

class CustomViewImageTheme extends ViewImageTheme {


	/*
	 * Build a page showing $image and some info about it
	 */
	public function display_page(Image $image, $editor_parts) {
		global $page;

		$h_metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));

		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->add_html_header("<meta name=\"keywords\" content=\"$h_metatags\">");
		$page->add_html_header("<meta property=\"og:title\" content=\"$h_metatags\">");
		$page->add_html_header("<meta property=\"og:type\" content=\"article\">");
		$page->add_html_header("<meta property=\"og:image\" content=\"".make_http($image->get_thumb_link())."\">");
		$page->add_html_header("<meta property=\"og:url\" content=\"".make_http(make_link("post/view/{$image->id}"))."\">");
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block(null, $this->build_pin($image), "subtoolbar", 0));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "left", 20));
	}

	public function display_admin_block(Page $page, $parts) {
		if(count($parts) > 0) {
			$page->add_block(new Block("Image Controls", join("<br>", $parts), "drawer", 50));
		}
	}

	protected function build_pin(Image $image) {

  		global $database;

  		if(isset($_GET['search'])) {
  			$search_terms = explode(' ', $_GET['search']);
  			$query = "search=".url_escape($_GET['search']);
  		}
  		else {
  			$search_terms = array();
  			$query = null;
  		}

    $h_prev = '<a id="prevlink" class="mdl-navigation__link mdl-js-ripple-effect" href="'.make_link("post/prev/{$image->id}", $query).'">Prev</a>';
		$h_index = "<a class='mdl-navigation__link mdl-js-ripple-effect' href='".make_link()."'>Current</a>";
    $h_next = '<a id="nextlink" class="mdl-navigation__link mdl-js-ripple-effect" href="'.make_link("post/next/{$image->id}", $query).'">Next</a>';

		return $h_prev.$h_index.$h_next;
	}


  	protected function build_info(Image $image, $editor_parts) {
  		global $user;

  		if(count($editor_parts) == 0) return ($image->is_locked() ? "<br>[Image Locked]" : "");

  		$html = make_form(make_link("post/set"))."
  					<input type='hidden' name='image_id' value='{$image->id}'>
  					<table style='' class='image_info form'>
  		";
  		foreach($editor_parts as $part) {
  			$html .= $part;
  		}
  		if(
  			(!$image->is_locked() || $user->can("edit_image_lock")) &&
  			$user->can("edit_image_tag")
  		) {
  			$html .= "
  						<tr><td colspan='4'>
  							<input class='view' type='button' value='Edit' onclick='$(\".view\").hide(); $(\".edit\").show();'>
  							<input class='edit' type='submit' value='Set'>
  						</td></tr>
  			";
  		}
  		$html .= "
  					</table>
  				</form>
  		";
  		return $html;
  	}

}
