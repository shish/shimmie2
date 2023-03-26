<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class CustomSetupTheme
 *
 * A customised version of the Setup theme.
 *
 */
class CustomUserConfigTheme extends UserConfigTheme
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
							Cookies.set(\"$i-hidden\", 'true', {path: '/'});
						}
						else {
							Cookies.set(\"$i-hidden\", 'false', {path: '/'});
						}
					});
				});
				if(Cookies.get(\"$i-hidden\") == 'true') {
					$(\"#$i\").hide();
				}
			});
			</script>
			<div class='setupblock'>
				<b id='$i-toggle'>$h</b>
				<br><div id='$i'>$b</div>
			</div>
		";

        return $this->rr($html);
    }
}
