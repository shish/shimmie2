<?php

class CustomViewTheme extends ViewTheme {
	public function display_image_not_found($page, $image_id) {
		$page->set_title("Image not found");
		$page->set_heading("Image not found");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Image not found",
			"No image in the database has the ID #$image_id"));
	}
	



	var $pin = null;

	protected function build_info($image) {
		global $user;
		$owner = $image->get_owner();
		$h_owner = html_escape($owner->name);
		$h_ip = html_escape($image->owner_ip);
		$h_source = html_escape($image->source);
		$i_owner_id = int_escape($owner->id);

		$html = "";
		$html .= "<p>Posted on {$image->posted} by <a href='".make_link("user/$h_owner")."'>$h_owner</a>";
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

		global $config;
		global $user;
		if($config->get_bool("tag_edit_anon") || ($user->id != $config->get_int("anon_id"))) {
			$html .= " (<a href=\"javascript: toggle('imgdata')\">edit</a>)";

			if(isset($_GET['search'])) {$h_query = "search=".url_escape($_GET['search']);}
			else {$h_query = "";}

			$h_tags = html_escape($image->get_tag_list());
			$i_image_id = int_escape($image->id);

			$html .= "
			<div id='imgdata'><form action='".make_link("tag_edit/set")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='hidden' name='query' value='$h_query'>
				<table style='width: 500px;'>
				<tr><td width='50px'>Tags</td><td width='300px'><input type='text' name='tags' value='$h_tags'></td></tr>
				<tr><td>Source</td><td><input type='text' name='source' value='$h_source'></td></tr>
				<tr><td>&nbsp;</td><td><input type='submit' value='Set'></td></tr>
				</table>
			</form>
			</div>
			";
		}

		return $html;
	}
}
?>
