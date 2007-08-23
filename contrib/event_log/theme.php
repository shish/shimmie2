<?php

class EventLogTheme extends Themelet {
	public function display_page($page, $events) {
		$page->set_title("Event Log");
		$page->set_heading("Event Log");
		$page->add_block(new NavBlock());
		
		$table = "
			<style>
			.event_log_table TD {
				font-size: 0.5em;
			}
			.event_log_table TD.entry {
				text-align: left;
			}
			</style>
			<table border='1' class='event_log_table'>
				<tr><th>ID</th><th>User</th><th>IP</th><th>Date</th><th>Event</th><th>Entry</th></tr>
		";
		foreach($events as $event) {
			$table .= "
				<tr>
					<td>{$event['id']}</td>
					<td>{$event['name']}</td>
					<td>{$event['owner_ip']}</td>
					<td>{$event['date']}</td>
					<td>{$event['event']}</td>
					<td class='entry'>{$event['entry']}</td>
				</tr>
			";
		}
		$table .= "</table>";
		$page->add_block(new Block("Log Contents", $table));
	}
}
?>
