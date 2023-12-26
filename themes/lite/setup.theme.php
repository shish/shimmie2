<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class CustomSetupTheme
 *
 * A customised version of the Setup theme.
 *
 */
class CustomSetupTheme extends SetupTheme
{
    protected function sb_to_html(SetupBlock $block): string
    {
        $h = $block->header;
        $b = $block->body;
        $i = preg_replace('/[^a-zA-Z0-9]/', '_', $h) . "-setup";
        $html = "
			<script type='text/javascript'>
			document.addEventListener('DOMContentLoaded', () => {
				$(\"#$i-toggle\").click(function() {
					$(\"#$i\").slideToggle(\"slow\", function() {
						if($(\"#$i\").is(\":hidden\")) {
							shm_cookie_set(\"$i-hidden\", 'true');
						}
						else {
							shm_cookie_set(\"$i-hidden\", 'false');
						}
					});
				});
				if(shm_cookie_get(\"$i-hidden\") == 'true') {
					$(\"#$i\").hide();
				}
			});
			</script>
			<div class='setupblock'>
				<b id='$i-toggle'>$h</b>
				<br><div id='$i'>$b</div>
			</div>
		";

        return "<div class='tframe'>$html</div>";
    }
}
