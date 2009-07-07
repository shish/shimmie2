<?php

class CustomSetupTheme extends SetupTheme {
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
