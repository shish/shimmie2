<?php

class Rule34Theme extends Themelet
{
    public function show_comic_changer(User $duser, bool $current_state): void
    {
        global $page;
        $checked = $current_state ? 'checked="checked"' : '';
        $html = make_form(make_link("rule34/comic_admin"), "POST");
        $html .= "<input type='hidden' name='user_id' value='{$duser->id}'>";
        $html .= "<label><input type='checkbox' name='is_admin' $checked> Comic Admin</label>";
        $html .= "<br/><input type='submit' id='Set'>";
        $html .= "</form>\n";
        
        $page->add_block(new Block("Rule34 Comic Options", $html));
    }

    public function display_bans(Page $page, array $bans)
    {
        global $database, $user;
        $h_bans = "";
        $prefix = ($database->get_driver_name() == Database::SQLITE_DRIVER ? "bans." : "");
        foreach ($bans as $ban) {
            $h_bans .= "
				<tr>
					<td width='12%'>{$ban[$prefix.'ip']}</td>
					<td>{$ban[$prefix.'reason']}</td>
					<td width='10%'>{$ban['banner_name']}</td>
					<td width='10%'>".substr($ban[$prefix.'time_start'], 0, 10)."</td>
					<td width='15%'>".substr($ban[$prefix.'time_end'], 0, 10)."</td>
					<!--
					".make_form(make_link("sys_ip_ban/remove"))."
					<td width='8%'>
							<input type='hidden' name='id' value='{$ban[$prefix.'id']}'>
							<input type='submit' value='Remove'>
					</td>
					</form>
					-->
				</tr>
			";
        }
        $html = "
			<a href='".make_link("sys_ip_ban/list", "all=on")."'>Show All</a>
			<p><table id='bans' class='sortable zebra'>
				<thead><tr><th>IP</th><th>Reason</th><th>By</th><th>From</th><th>Until</th><!-- <th>Action</th>--></tr></thead>
				$h_bans
				<!--
				<tfoot><tr id='add'>
					".make_form(make_link("sys_ip_ban/add"))."
						<td><input type='text' name='ip' value='".html_escape(@$_GET['ip'])."'></td>
						<td><input type='text' name='reason' value='".html_escape(@$_GET['reason'])."'></td>
						<td>{$user->name}</td>
						<td></td>
						<td><input type='text' name='end' value='".html_escape(@$_GET['end'])."'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr></tfoot>
				-->
			</table>
		";
        $page->set_title("IP Bans");
        $page->set_heading("IP Bans");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Edit IP Bans", $html));
    }
}
