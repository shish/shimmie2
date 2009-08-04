<?php
/**
 * A customised version of the Setup theme
 */
class CustomSetupTheme extends SetupTheme {
	protected function sb_to_html(SetupBlock $block) {
		return $this->box(parent::sb_to_html($block));
	}
}
?>
