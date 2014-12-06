<?php

class PrivMsgTheme extends Themelet {
	public function display_pms(Page $page, $pms) {
		global $user;

		$html = "
			<table id='pms' class='zebra sortable'>
				<thead><tr><th>R?</th><th>Subject</th><th>From</th><th>Date</th><th>Action</th></tr></thead>
				<tbody>";
		foreach($pms as $pm) {
			$h_subject = html_escape($pm->subject);
			if(strlen(trim($h_subject)) == 0) $h_subject = "(No subject)";
			$from = User::by_id($pm->from_id);
			$from_name = $from->name;
			$h_from = html_escape($from_name);
			$from_url = make_link("user/".url_escape($from_name));
			$pm_url = make_link("pm/read/".$pm->id);
			$del_url = make_link("pm/delete");
			$h_date = html_escape($pm->sent_date);
			$readYN = "Y";
			if(!$pm->is_read) {
				$h_subject = "<b>$h_subject</b>";
				$readYN = "N";
			}
			$hb = $from->can("hellbanned") ? "hb" : "";
			$html .= "<tr class='$hb'>
			<td>$readYN</td>
			<td><a href='$pm_url'>$h_subject</a></td>
			<td><a href='$from_url'>$h_from</a></td><td>$h_date</td>
			<td><form action='$del_url' method='POST'>
				<input type='hidden' name='pm_id' value='{$pm->id}'>
				".$user->get_auth_html()."
				<input type='submit' value='Delete'>
			</form></td>
			</tr>";
		}
		$html .= "
				</tbody>
			</table>
		";
		$page->add_block(new Block("Private Messages", $html, "main", 40, "private-messages"));
	}

	public function display_composer(Page $page, User $from, User $to, $subject="") {
		global $user;
		$post_url = make_link("pm/send");
		$h_subject = html_escape($subject);
		$to_id = $to->id;
		$auth = $user->get_auth_html();
		$html = <<<EOD
<form action="$post_url" method="POST">
$auth
<input type="hidden" name="to_id" value="$to_id">
<table style="width: 400px;" class="form">
<tr><th>Subject:</th><td><input type="text" name="subject" value="$h_subject"></td></tr>
<tr><td colspan="2"><textarea style="width: 100%" rows="6" name="message"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Send"></td></tr>
</table>
</form>
EOD;
		$page->add_block(new Block("Write a PM", $html, "main", 50));
	}

	public function display_message(Page $page, User $from, User $to, PM $pm) {
		$this->display_composer($page, $to, $from, "Re: ".$pm->subject);
		$page->set_title("Private Message");
		$page->set_heading(html_escape($pm->subject));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Message from {$from->name}", format_text($pm->message), "main", 10));
	}
}

