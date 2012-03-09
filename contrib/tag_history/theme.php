<?php
/*
 * Name: Tag History
 * Author: Bzchan <bzchan@animemahou.com>, modified by jgen <jgen.tech@gmail.com>
 */

class Tag_HistoryTheme extends Themelet {
	var $messages = array();

	public function display_history_page(Page $page, /*int*/ $image_id, /*array*/ $history) {
		global $user;
		$start_string = "
			<div style='text-align: left'>
				".make_form(make_link("tag_history/revert"))."
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
			$h_ip = $user->can("view_ip") ? " ".show_ip($fields['user_ip'], "Tagging Image #$image_id as '$current_tags'") : "";
			$setter = "<a href='".make_link("user/".url_escape($name))."'>".html_escape($name)."</a>$h_ip";

			$selected = ($n == 2) ? " checked" : "";
			$history_list .= "
				<li>
					<input type='radio' name='revert' id='$current_id' value='$current_id'$selected>
					<label for='$current_id'>$current_tags (Set by $setter)</label>
				</li>
				";
		}

		$end_string = "
					</ul>
					<input type='submit' value='Revert To'>
				</form>
			</div>
		";
		$history_html = $start_string . $history_list . $end_string;

		$page->set_title('Image '.$image_id.' Tag History');
		$page->set_heading('Tag History: '.$image_id);
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Tag History", $history_html, "main", 10));
	}

	public function display_global_page(Page $page, /*array*/ $history, /*int*/ $page_number) {
		$start_string = "
			<div style='text-align: left'>
				".make_form(make_link("tag_history/revert"))."
					<ul style='list-style-type:none;'>
		";
		$end_string = "
					</ul>
					<input type='submit' value='Revert To'>
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
			$h_ip = $user->can("view_ip") ? " ".show_ip($fields['user_ip'], "Tagging Image #$image_id as '$current_tags'") : "";
			$setter = "<a href='".make_link("user/".url_escape($name))."'>".html_escape($name)."</a>$h_ip";

			$history_list .= '
				<li>
					<input type="radio" name="revert" value="'.$current_id.'">
					<a href="'.make_link('post/view/'.$image_id).'">'.$image_id.'</a>:
					'.$current_tags.' (Set by '.$setter.')
				</li>
			';
		}

		$history_html = $start_string . $history_list . $end_string;
		$page->set_title("Global Tag History");
		$page->set_heading("Global Tag History");
		$page->add_block(new Block("Tag History", $history_html, "main", 10));


		$h_prev = ($page_number <= 1) ? "Prev" :
			'<a href="'.make_link('tag_history/all/'.($page_number-1)).'">Prev</a>';
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = '<a href="'.make_link('tag_history/all/'.($page_number+1)).'">Next</a>';

		$nav = $h_prev.' | '.$h_index.' | '.$h_next;
		$page->add_block(new Block("Navigation", $nav, "left"));
	}

	public function display_history_link(Page $page, /*int*/ $image_id) {
		$link = '<a href="'.make_link('tag_history/'.$image_id).'">Tag History</a>';
		$page->add_block(new Block(null, $link, "main", 5));
	}
	
	/*
	 * Add a section to the admin page.
	 */
	public function display_admin_block(/*string*/ $validation_msg='') {
		global $page;
		
		if (!empty($validation_msg)) {
			$validation_msg = '<br><b>'. $validation_msg .'</b>';
		}
		
		$html = '
			Revert tag changes/edit by a specific IP address.<br>
			You can restrict the time frame to revert these edits as well.
			<br>(Date format: 2011-10-23)
			'.$validation_msg.'

			<br><br>'.make_form(make_link("admin/revert_ip"),'POST',false,'revert_ip_form')."
				IP Address: <input type='text' id='revert_ip' name='revert_ip' size='15'><br>
				Date range: <input type='text' id='revert_date' name='revert_date' size='15'><br><br>
				<input type='submit' value='Revert' onclick='return confirm(\"Revert all edits by this IP?\");'>
			</form><br>
		";
		$page->add_block(new Block("Revert By IP", $html));
	}
	
	/*
	 * Show a standard page for results to be put into
	 */
	public function display_revert_ip_results() {
		global $page;
		$html = implode($this->messages, "\n");
		$page->add_block(new Block("Revert by IP", $html));
	}
	
	public function add_status(/*string*/ $title, /*string*/ $body) {
		$this->messages[] = '<p><b>'. $title .'</b><br>'. $body .'</p>';
	}
}
?>
