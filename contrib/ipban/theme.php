<?php

class IPBanTheme extends Themelet {
	/*
	 * Show all the bans
	 *
	 * $bans = an array of (
	 *  'ip' => the banned IP
	 *  'reason' => why the IP was banned
	 *  'date' => when the ban started
	 *  'end' => when the ban will end
	 * )
	 */
	public function display_bans(Page $page, $bans) {
		global $user;
		$h_bans = "";
		foreach($bans as $ban) {
			$end_human = date('Y-m-d', $ban['end_timestamp']);
			$h_bans .= "
				<tr>
					<td>{$ban['ip']}</td>
					<td>{$ban['reason']}</td>
					<td>{$ban['banner_name']}</td>
					<td>{$end_human}</td>
					<td>
						<form action='".make_link("ip_ban/remove")."' method='POST'>
							<input type='hidden' name='id' value='{$ban['id']}'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<table border='1'>
				<thead><td>IP</td><td>Reason</td><td>By</td><td>Until</td><td>Action</td></thead>
				$h_bans
				<tr>
					<form action='".make_link("ip_ban/add")."' method='POST'>
						<td><input type='text' name='ip'></td>
						<td><input type='text' name='reason'></td>
						<td>{$user->name}</td>
						<td><input type='text' name='end'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr>
			</table>
		";
		$page->set_title("IP Bans");
		$page->set_heading("IP Bans");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Edit IP Bans", $html));
	}
}
?>
