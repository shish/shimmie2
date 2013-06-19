<?php

class CustomExtManagerTheme extends ExtManagerTheme {
	public function display_table(Page $page, /*array*/ $extensions, /*bool*/ $editable) {
		$page->disable_left();
		parent::display_table($page, $extensions, $editable);
	}
	public function display_doc(Page $page, ExtensionInfo $info) {
		$page->disable_left();
		parent::display_doc($page, $info);
	}
}

?>
