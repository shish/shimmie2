<?php

class Tag_HistoryTheme extends Themelet {
	public function display_history_page(Page $page, $image_id, $history) {
		global $user;
		$start_string = "
			<div style='text-align: left'>
				<form enctype='multipart/form-data' action='".make_link("tag_history/revert")."' method='POST'>
					<ul style='list-style-type:none;'>
		";

		$history_list = "";
		$n = 0;
		foreach($history as $fields)
		{
			$n++;
			$current_id = $fields['id'];
			$current_tags = html_escape($fields['tags']);
			$name = $fields['name'];
			$setter = "<a href='".make_link("user/".url_escape($name))."'>".html_escape($name)."</a>";
			if($user->is_admin()) {
				$setter .= " / " . $fields['user_ip'];
			}
			$selected = ($n == 2) ? " checked" : "";
			$history_list .= "<li><input type='radio' name='revert' value='$current_id'$selected>$current_tags (Set by $setter)</li>\n";
		}

		$end_string = "
					</ul>
					<input type='submit' value='Revert'>
				</form>
			</div>
		";
		$history_html = $start_string . $history_list . $end_string;

		$page->set_title("Image $image_id Tag History");
		$page->set_heading("Tag History: $image_id");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Tag History", $history_html, "main", 10));
	}

	public function display_global_page(Page $page, $history) {
		$start_string = "
			<div style='text-align: left'>
				<form enctype='multipart/form-data' action='".make_link("tag_history/revert")."' method='POST'>
					<ul style='list-style-type:none;'>
		";
		$end_string = "
					</ul>
					<input type='submit' value='Revert'>
				</form>
			</div>
		";

		global $user;
		$history_list = "";
		foreach($history as $fields)
		{
			$current_id = $fields['id'];
			$image_id = $fields['image_id'];
			$current_tags = html_escape($fields['tags']);
			$name = $fields['name'];
			$setter = "<a href='".make_link("user/".url_escape($name))."'>".html_escape($name)."</a>";
			if($user->is_admin()) {
				$setter .= " / " . $fields['user_ip'];
			}
			$history_list .= "
				<li>
					<input type='radio' name='revert' value='$current_id'>
					<a href='".make_link("post/view/$image_id")."'>$image_id</a>:
					$current_tags (Set by $setter)
				</li>
			";
		}

		$history_html = $start_string . $history_list . $end_string;
		$page->set_title("Global Tag History");
		$page->set_heading("Global Tag History");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Tag History", $history_html, "main", 10));
	}

	public function display_history_link(Page $page, $image_id) {
		$link = "<a href='".make_link("tag_history/$image_id")."'>Tag History</a>\n";
		$page->add_block(new Block(null, $link, "main", 5));
	}
}
?>
