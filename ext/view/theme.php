<?php

class ViewImageTheme extends Themelet {
	/*
	 * Build a page showing $image and some info about it
	 */
	public function display_page(Page $page, Image $image, $editor_parts) {
		$metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));

		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->add_header("<meta name=\"keywords\" content=\"$metatags\">");
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block("Navigation", $this->build_navigation($image), "left", 0));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 10));
		//$page->add_block(new Block(null, $this->build_pin($image), "main", 11));
	}

	public function display_admin_block(Page $page, $parts) {
		if(count($parts) > 0) {
			$page->add_block(new Block("Image Controls", join("<br>", $parts), "left", 50));
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

		$h_prev = "<a id='prevlink' href='".make_link("post/prev/{$image->id}", $query)."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = "<a id='nextlink' href='".make_link("post/next/{$image->id}", $query)."'>Next</a>";
		$script = "
		<script><!--
		$(document).ready(function() {
			if(document.location.hash.length > 3) {
				query = document.location.hash.substring(1);
				a = document.getElementById(\"prevlink\");
				a.href = a.href + '?' + query;
				a = document.getElementById(\"nextlink\");
				a.href = a.href + '?' + query;
			}
		});
		//--></script>
			";

		return "$h_prev | $h_index | $h_next$script";
	}

	protected function build_navigation(Image $image) {
		$h_pin = $this->build_pin($image);
		$h_search = "
			<script><!--
			$(document).ready(function() {
				$(\"#search_input\").DefaultValue(\"Search\");
			});
			//--></script>
			<p><form action='".make_link()."' method='GET'>
				<input id='search_input' name='search' type='text'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
			<div id='search_completions'></div>";

		return "$h_pin<br>$h_search";
	}

	protected function build_info(Image $image, $editor_parts) {
		global $user;
		$owner = $image->get_owner();
		$h_owner = html_escape($owner->name);
		$h_ip = html_escape($image->owner_ip);
		$h_source = html_escape($image->source);
		$i_owner_id = int_escape($owner->id);
		$h_date = autodate($image->posted);

		$html = "";
		$html .= "<p>Uploaded by <a href='".make_link("user/$h_owner")."'>$h_owner</a> $h_date";

		if($user->is_admin()) {
			$html .= " ($h_ip)";
		}
		if(!is_null($image->source)) {
			if(substr($image->source, 0, 7) == "http://") {
				$html .= " (<a href='$h_source'>source</a>)";
			}
			else {
				$html .= " (<a href='http://$h_source'>source</a>)";
			}
		}

		$html .= $this->build_image_editor($image, $editor_parts);

		return $html;
	}

	protected function build_image_editor(Image $image, $editor_parts) {
		if(count($editor_parts) == 0) return ($image->is_locked() ? "<br>[Image Locked]" : "");

		if(isset($_GET['search'])) {$h_query = "search=".url_escape($_GET['search']);}
		else {$h_query = "";}

		$html = " (<a href=\"javascript: toggle('imgdata')\">edit info</a>)";
		$html .= "
			<div id='imgdata'>
				<form action='".make_link("post/set")."' method='POST'>
					<input type='hidden' name='image_id' value='{$image->id}'>
					<input type='hidden' name='query' value='$h_query'>
					<table style='width: 500px;'>
		";
		foreach($editor_parts as $part) {
			$html .= $part;
		}
		$html .= "
						<tr><td colspan='2'><input type='submit' value='Set'></td></tr>
					</table>
				</form>
				<br>
			</div>
		";
		return $html;
	}
}
?>
