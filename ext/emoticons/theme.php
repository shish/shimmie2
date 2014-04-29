<?php
class EmoticonListTheme extends Themelet {
	/**
	 * @param array $list
	 */
	public function display_emotes(/*array*/ $list) {
		global $page;
		$data_href = get_base_href();
		$html = "<html><head><title>Emoticon list</title></head><body>";
		$html .= "<table><tr>";
		$n = 1;
		foreach($list as $item) {
			$pathinfo = pathinfo($item);
			$name = $pathinfo["filename"];
			$html .= "<td><img src='$data_href/$item'> :$name:</td>";
			if($n++ % 3 == 0) $html .= "</tr><tr>";
		}
		$html .= "</tr></table>";
		$html .= "</body></html>";
		$page->set_mode("data");
		$page->set_data($html);
	}
}

