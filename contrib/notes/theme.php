<?php

class NotesTheme extends Themelet {
	public function display_notes(Page $page, $notes) {
		$html = <<<EOD
<script type="text/javascript">
img = byId("main_image");
</script>
EOD;
		$page->add_block(new Block(null, $html));
	}
}
?>
