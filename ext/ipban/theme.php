<?php

class IPBanTheme extends Themelet
{
    /*
     * Show all the bans
     *
     * $bans = an array of (
     *  'ip' => the banned IP
     *  'reason' => why the IP was banned
     *  'added' => when the ban started
     *  'expires' => when the ban will end
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
					<td width='10%'>{$ban['banner']}</td>
					<td width='10%'>".substr($ban[$prefix.'added'], 0, 10)."</td>
					<td width='10%'>".substr($ban[$prefix.'expires'], 0, 10)."</td>
					<td width='10%'>{$ban['mode']}</td>
					".make_form(make_link("ip_ban/delete"))."
					<td width='8%'>
							<input type='hidden' name='d_id' value='{$ban[$prefix.'id']}'>
							<input type='submit' value='Remove'>
					</td>
					</form>
				</tr>
			";
        }
        $today = date('Y-m-d');
        $html = "
			<a href='".make_link("ip_ban/list", "r__size=1000000")."'>Show All Active</a> /
			<a href='".make_link("ip_ban/list", "r_all=on&r__size=1000000")."'>Show EVERYTHING</a>
			<p><table id='bans' class='sortable zebra'>
				<thead>
					<tr><th>IP</th><th>Reason</th><th>By</th><th>From</th><th>Until</th><th>Type</th><th>Action</th></tr>
					<tr>
						".make_form(make_link("ip_ban/list"), "GET")."
							<td><input type='text' name='r_ip' value='".html_escape(@$_GET['r_ip'])."'></td>
							<td><input type='text' name='r_reason' value='".html_escape(@$_GET['r_reason'])."'></td>
							<td><input type='text' name='r_banner' value='".html_escape(@$_GET['r_banner'])."'></td>
							<td><input type='text' name='r_added' value='".html_escape(@$_GET['r_added'])."'></td>
							<td><input type='text' name='r_expires' value='".html_escape(@$_GET['r_expires'])."'></td>
							<td><input type='text' name='r_mode' value='".html_escape(@$_GET['r_mode'])."'></td>
							<td><input type='submit' value='Search'></td>
						</form>
					</tr>
				</thead>
				$h_bans
				<tfoot>
					<tr id='add'>
						".make_form(make_link("ip_ban/create"))."
							<td><input type='text' name='c_ip' value='".html_escape(@$_GET['c_ip'])."'></td>
							<td><input type='text' name='c_reason' value='".html_escape(@$_GET['c_reason'])."'></td>
							<td>{$user->name}</td>
							<td>{$today}</td>
							<td><input type='text' name='c_expires' value='".html_escape(@$_GET['c_expires'])."'></td>
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
