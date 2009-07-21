<?php
/**
 * A customised version of the Setup theme
 */
class CustomSetupTheme extends SetupTheme {
	/**
	 * Turn a SetupBlock into HTML... with rounded corners.
	 */
	protected function sb_to_html(SetupBlock $block) {
		return "
			<div class='rr setupblock'>
				<div class='rrtop'><div></div></div>
				<div class='rrcontent'><b>{$block->header}</b><br>{$block->body}</div>
				<div class='rrbot'><div></div></div>
			</div>
		";
	}
}
?>
