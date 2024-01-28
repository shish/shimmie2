<?php

declare(strict_types=1);

namespace Shimmie2;

class MP3FileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        global $page;
        $data_href = get_base_href();
        $ilink = $image->get_image_link();
        $html = "
			<audio controls class='shm-main-image audio_image' id='main_image' alt='main image'>
				<source id='audio_src' src=\"$ilink\" type=\"audio/mpeg\">
				Your browser does not support the audio element.
			</audio>
			<p>Title: <span id='audio-title'>???</span> | Artist: <span id='audio-artist'>???</span></p>

			<p><a href='$ilink' id='audio-download'>Download</a>";

        $page->add_html_header("<script src='{$data_href}/ext/handle_mp3/lib/jsmediatags.min.js' type='text/javascript'></script>");
        $page->add_block(new Block("Music", $html, "main", 10));
    }
}
