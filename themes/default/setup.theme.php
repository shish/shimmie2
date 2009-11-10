<?php
/**
 * A customised version of the Setup theme
 */
class CustomSetupTheme extends SetupTheme {
	protected function sb_to_html(SetupBlock $block) {
		$h = $block->header;
		$b = $block->body;
		$i = preg_replace('/[^a-zA-Z0-9]/', '_', $h) . "-setup";
		$html = "
			<script><!--
			$(document).ready(function() {
				$(\"#$i-toggle\").click(function() {
					$(\"#$i\").slideToggle(\"slow\", function() {
						if($(\"#$i\").is(\":hidden\")) {
							$.cookie(\"$i-hidden\", 'true', {path: '/'});
						}
						else {
							$.cookie(\"$i-hidden\", 'false', {path: '/'});
						}
					});
				});
				if($.cookie(\"$i-hidden\") == 'true') {
					$(\"#$i\").hide();
				}
			});
			//--></script>
			<div class='setupblock'>
				<b id='$i-toggle'>$h</b>
				<br><div id='$i'>$b</div>
			</div>
		";

		return $this->rr($html);
	}
}
?>
