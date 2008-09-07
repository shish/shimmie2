<?php

class PMTheme extends Themelet {
	public function display_pms(Page $page, $pms) {
		$html = "<table>";
		$html .= "<tr><th>Subject</th><th>From</th><th>Date</th><th>Action</th></tr>";
		foreach($pms as $pm) {
			$h_subject = html_escape($pm["subject"]);
			$h_from = html_escape($pm["from_name"]);
			$from_url = make_link("user/".url_escape($pm["from_name"]));
			$pm_url = make_link("pm/read/".$pm["id"]);
			$del_url = make_link("pm/delete/".$pm["id"]);
			$h_date = html_escape($pm["sent_date"]);
			if($pm["is_read"] == "N") $h_subject = "<b>$h_subject</b>";
			$html .= "<tr><td><a href='$pm_url'>$h_subject</a></td>
			<td><a href='$from_url'>$h_from</a></td><td>$h_date</td>
			<td><form action='$del_url'><input type='submit' value='Delete'></form></td></tr>";
		}
		$html .= "</table>";
		$page->add_block(new Block("Private Messages", $html, "main", 10));
	}

	public function display_composer(Page $page, User $from, User $to) {
		$post_url = make_link("pm/send");
		$to_id = $to->id;
		$html = <<<EOD
<form action="$post_url" method="POST">
<input type="hidden" name="to_id" value="$to_id">
<table style="width: 400px;">
<tr><td>Subject:</td><td><input type="text" name="subject"></td></tr>
<tr><td colspan="2"><textarea style="width: 100%" rows="6" name="message"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Send"></td></tr>
</table>
</form>
EOD;
		$page->add_block(new Block("Write a PM", $html, "main", 20));
	}

	public function display_message(Page $page, User $from, User $to, $pm) {
		$this->display_composer($page, $to, $from);
		$page->set_title("Private Message");
		$page->set_heading(html_escape($pm["subject"]));
		$page->add_block(new Block("Message", format_text($pm["message"]), "main", 10));
	}
}
?>
