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
		global $database, $user;
		$h_bans = "";
		$n = 0;
		$prefix = ($database->engine->name == "sqlite" ? "bans." : "");
		$prefix2 = ($database->engine->name == "sqlite" ? "users." : "");
		foreach($bans as $ban) {
			$end_human = date('Y-m-d', $ban[$prefix.'end_timestamp']);
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$h_bans .= "
				<tr class='$oe'>
					<td width='10%'>{$ban[$prefix.'ip']}</td>
					<td>{$ban[$prefix.'reason']}</td>
					<td width='10%'>{$ban['banner_name']}</td>
					<td width='15%'>{$end_human}</td>
					<td width='10%'>
						<form action='".make_link("ip_ban/remove")."' method='POST'>
							<input type='hidden' name='id' value='{$ban[$prefix.'id']}'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#bans\").tablesorter();
			});
			</script>
			<a href='".make_link("ip_ban/list", "all=on")."'>Show All</a>
			<p><table id='bans' class='zebra'>
				<thead><tr><th>IP</th><th>Reason</th><th>By</th><th>Until</th><th>Action</th></tr></thead>
				$h_bans
				<tfoot><tr>
					<form action='".make_link("ip_ban/add")."' method='POST'>
						<td><input type='text' name='ip'></td>
						<td><input type='text' name='reason'></td>
						<td>{$user->name}</td>
						<td><input type='text' name='end'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr></tfoot>
			</table>
		";
		$page->set_title("IP Bans");
		$page->set_heading("IP Bans");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Edit IP Bans", $html));
	}
}
?>
