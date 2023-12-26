<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomHomeTheme extends HomeTheme
{
    public function build_body(string $sitename, string $main_links, string $main_text, string $contact_link, $num_comma, string $counter_text): string
    {
        $main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
        $message_html = empty($main_text) ? "" : "<div class='space' id='message'>$main_text</div>";
        $counter_html = empty($counter_text) ? "" : "<div class='space' id='counter'>$counter_text</div>";
        $contact_link = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a> &ndash;";
        $search_html = "
			<div class='space' id='search'>
				<form action='".make_link("post/list")."' method='GET'>
				<input name='search' size='60' type='text' value='' class='autocomplete_tags' autofocus='autofocus'/>
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Search' style=\"border: 1px solid #888; height: 30px; border-radius: 2px; background: #EEE;\"/>
				</form>
			</div>
		";
        return "
		<div id='front-page'>
			<h1><a style='text-decoration: none;' href='".make_link(). "'><span>$sitename</span></a></h1>
			$main_links_html
			$search_html
			$message_html
			$counter_html
			<div class='space' id='foot'>

<!-- JuicyAds v3.1 -->
<script type='text/javascript' data-cfasync='false' async src='https://poweredby.jads.co/js/jads.js'></script>
<ins id='825625' data-width='908' data-height='270'></ins>
<script type='text/javascript' data-cfasync='false' async>(adsbyjuicy = window.adsbyjuicy || []).push({'adzone':825625});</script>
<!--JuicyAds END-->

<script type='application/javascript' src='https://a.realsrv.com/video-slider.js'></script>
<script type='application/javascript'>
var adConfig = {
    'idzone': 3465907,
    'frequency_period': 720,
    'close_after': 0,
    'on_complete': 'hide',
    'branding_enabled': 1,
    'screen_density': 25,
    'cta_enabled': 0
};
ExoVideoSlider.init(adConfig);
</script>

				<small><small>
				$contact_link Serving $num_comma posts &ndash;
				Running <a href='https://code.shishnet.org/shimmie2/'>Shimmie2</a>
				</small></small>
			</div>
		</div>";
    }
}
