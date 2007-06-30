<?php

class IPBanTheme extends Themelet {
	public function display_bans($page, $bans) {
		$h_bans = "";
		foreach($bans as $ban) {
			$h_bans .= "
				<tr>
					<td>{$ban['ip']}</td>
					<td>{$ban['reason']}</td>
					<td>{$ban['end']}</td>
					<td>
						<form action='".make_link("ip_ban/remove")."' method='POST'>
							<input type='hidden' name='ip' value='{$ban['ip']}'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<table border='1'>
				<thead><td>IP</td><td>Reason</td><td>Until</td><td>Action</td></thead>
				$h_bans
				<tr>
					<form action='".make_link("ip_ban/add")."' method='POST'>
						<td><input type='text' name='ip'></td>
						<td><input type='text' name='reason'></td>
						<td><input type='text' name='end'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr>
			</table>
		";
		$page->add_block(new Block("Edit IP Bans", $html));
	}
}
?>
