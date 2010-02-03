<?php

class LogDatabaseTheme extends Themelet {
	protected function heie($var) {
		if(isset($_GET[$var])) return html_escape($_GET[$var]);
		else return "";
	}
	public function display_events($events) {
		$table = "
<style>
.sizedinputs TD INPUT {
	width: 100%;
}
</style>
<table class='zebra'>
	<thead>
		<tr><th>Time</th><th>Module</th><th>User</th><th>Message</th></tr>
		<form action='".make_link("log/view")."' method='GET'>
			<tr class='sizedinputs'>
				<td><input type='text' name='time' value='".$this->heie("time")."'></td>
				<td><input type='text' name='module' value='".$this->heie("module")."'></td>
				<td><input type='text' name='user' value='".$this->heie("user")."'></td>
				<td>
					<select name='priority'>
						<option value='".SCORE_LOG_DEBUG."'>Debug</option>
						<option value='".SCORE_LOG_INFO."' selected>Info</option>
						<option value='".SCORE_LOG_WARNING."'>Warning</option>
						<option value='".SCORE_LOG_ERROR."'>Error</option>
						<option value='".SCORE_LOG_CRITICAL."'>Critical</option>
					</select>
					<input type='submit' value='Search' style='width: 20%'>
				</td>
			</tr>
		</form>
	</thead>
	<tbody>\n";
		$n = 0;
		foreach($events as $event) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$c = $this->pri_to_col($event['priority']);
			$table .= "<tr style='color: $c' class='$oe'>";
			$table .= "<td>".str_replace(" ", "&nbsp;", $event['date_sent'])."</td>";
			$table .= "<td>".$event['section']."</td>";
			if($event['username'] == "Anonymous") {
				$table .= "<td>".$event['address']."</td>";
			}
			else {
				$table .= "<td><span title='".$event['address']."'>".
					"<a href='".make_link("user/".url_escape($event['username']))."'>".html_escape($event['username'])."</a>".
					"</span></td>";
			}
			$table .= "<td>".$this->scan_entities(html_escape($event['message']))."</td>";
			$table .= "</tr>\n";
		}
		$table .= "</tbody></table>";

		global $page;
		$page->set_title("Event Log");
		$page->set_heading("Event Log");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Events", $table));
	}

	protected function pri_to_col($pri) {
		switch($pri) {
			case SCORE_LOG_DEBUG: return "#999";
			case SCORE_LOG_INFO: return "#000";
			case SCORE_LOG_WARNING: return "#800";
			case SCORE_LOG_ERROR: return "#C00";
			case SCORE_LOG_CRITICAL: return "#F00";
			default: return "";
		}
	}

	protected function scan_entities($line) {
		$line = preg_replace_callback("/Image #(\d+)/s", array($this, "link_image"), $line);
		return $line;
	}

	protected function link_image($id) {
		$iid = int_escape($id[1]);
		return "<a href='".make_link("post/view/$iid")."'>Image #$iid</a>";
	}
}
?>
