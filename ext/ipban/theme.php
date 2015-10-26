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
		$prefix = ($database->get_driver_name() == "sqlite" ? "bans." : "");
		foreach($bans as $ban) {
			$end_human = date('Y-m-d', $ban[$prefix.'end_timestamp']);
			$h_bans .= "
				<tr>
					<td width='12%'>{$ban[$prefix.'ip']}</td>
					<td>{$ban[$prefix.'reason']}</td>
					<td width='10%'>{$ban['banner_name']}</td>
					<td width='10%'>".substr($ban[$prefix.'added'], 0, 10)."</td>
					<td width='15%'>{$end_human}</td>
					".make_form(make_link("ip_ban/remove"))."
					<td width='8%'>
							<input type='hidden' name='id' value='{$ban[$prefix.'id']}'>
							<input type='submit' value='Remove'>
					</td>
					</form>
				</tr>
			";
		}
		$html = "
			<a href='".make_link("ip_ban/list", "all=on")."'>Show All</a>
			<p><table id='bans' class='sortable zebra'>
				<thead><tr><th>IP</th><th>Reason</th><th>By</th><th>From</th><th>Until</th><th>Action</th></tr></thead>
				$h_bans
				<tfoot><tr id='add'>
					".make_form(make_link("ip_ban/add"))."
						<td><input type='text' name='ip' value='".html_escape(@$_GET['ip'])."'></td>
						<td><input type='text' name='reason' value='".html_escape(@$_GET['reason'])."'></td>
						<td>{$user->name}</td>
						<td></td>
						<td><input type='text' name='end' value='".html_escape(@$_GET['end'])."'></td>
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

