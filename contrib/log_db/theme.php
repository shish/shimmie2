<?php

class LogDatabaseTheme extends Themelet {
	public function display_events($events) {
		$table = "<table class='zebra'>";
		$table .= "<thead><th>Time</th><th>Module / Priority</th><th>Username / Address</th><th>Message</th></thead>";
		$table .= "<tbody>";
		$n = 0;
		foreach($events as $event) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$table .= "<tr class='$oe'>";
			$table .= "<td>".$event['date_sent']."</td>";
			$table .= "<td>".$event['section']." / ".$event['priority']."</td>";
			$table .= "<td>".html_escape($event['username'])." / ".$event['address']."</td>";
			$table .= "<td>".html_escape($event['message'])."</td>";
			$table .= "</tr>";
		}
		$table .= "</tbody></table>";

		global $page;
		$page->set_title("Event Log");
		$page->set_heading("Event Log");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Events", $table));
	}
}
?>
