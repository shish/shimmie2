<?php

class IPBanTheme extends Themelet
{
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
    public function display_bans(Page $page, $bans)
    {
        global $database, $user;
        $h_bans = "";
        $prefix = ($database->get_driver_name() == DatabaseDriver::SQLITE ? "bans." : "");
        foreach ($bans as $ban) {
            $h_bans .= "
				<tr>
					<td width='12%'>{$ban[$prefix.'ip']}</td>
					<td>{$ban[$prefix.'reason']}</td>
					<td width='10%'>{$ban['banner_name']}</td>
					<td width='10%'>".substr($ban[$prefix.'added'], 0, 10)."</td>
					<td width='10%'>".substr($ban[$prefix.'expires'], 0, 10)."</td>
					<td width='10%'>{$ban['mode']}</td>
					".make_form(make_link("ip_ban/remove"))."
					<td width='8%'>
							<input type='hidden' name='id' value='{$ban[$prefix.'id']}'>
							<input type='submit' value='Remove'>
					</td>
					</form>
				</tr>
			";
        }
        $today = date('Y-m-d');
        $html = "
			<a href='".make_link("ip_ban/list", "limit=1000000")."'>Show All Active</a> /
			<a href='".make_link("ip_ban/list", "all=on&limit=1000000")."'>Show EVERYTHING</a>
			<p><table id='bans' class='sortable zebra'>
				<thead>
					<tr><th>IP</th><th>Reason</th><th>By</th><th>From</th><th>Until</th><th>Type</th><th>Action</th></tr>
					<tr>
						".make_form(make_link("ip_ban/list/1"), "GET")."
							<td><input type='text' name='s_ip' value='".html_escape(@$_GET['s_ip'])."'></td>
							<td><input type='text' name='s_reason' value='".html_escape(@$_GET['s_reason'])."'></td>
							<td><input type='text' name='s_banner' value='".html_escape(@$_GET['s_banner'])."'></td>
							<td><input type='text' name='s_added' value='".html_escape(@$_GET['s_added'])."'></td>
							<td><input type='text' name='s_expires' value='".html_escape(@$_GET['s_expires'])."'></td>
							<td><input type='text' name='s_mode' value='".html_escape(@$_GET['s_mode'])."'></td>
							<td><input type='submit' value='Search'></td>
						</form>
					</tr>
				</thead>
				$h_bans
				<tfoot>
					<tr id='add'>
						".make_form(make_link("ip_ban/add"))."
							<td><input type='text' name='ip' value='".html_escape(@$_GET['ip'])."'></td>
							<td><input type='text' name='reason' value='".html_escape(@$_GET['reason'])."'></td>
							<td>{$user->name}</td>
							<td>{$today}</td>
							<td><input type='text' name='end' value='".html_escape(@$_GET['end'])."'></td>
							<td>block</td>
							<td><input type='submit' value='Ban'></td>
						</form>
					</tr>
				</tfoot>
			</table>
		";
        $page->set_title("IP Bans");
        $page->set_heading("IP Bans");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Edit IP Bans", $html));
    }
}
