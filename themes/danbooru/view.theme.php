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

	protected function build_info($image, $editor_parts) {
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

		$html .= $this->build_image_editor($image, $editor_parts);

		return $html;
	}
}
?>
