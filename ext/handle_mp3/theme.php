<?php

declare(strict_types=1);

namespace Shimmie2;

class MP3FileHandlerTheme extends Themelet
{
    public function display_image(Page $page, Image $image)
    {
        $data_href = get_base_href();
        $ilink = $image->get_image_link();
        $html = "
			<audio controls class='shm-main-image audio_image' id='main_image' alt='main image'>
				<source src=\"$ilink\" type=\"audio/mpeg\">
				Your browser does not support the audio element.
			</audio>
			<p>Title: <span id='audio-title'>???</span> | Artist: <span id='audio-artist'>???</span></p>

			<script>
				$('#main_image').prop('volume', 0.25);

				var jsmediatags = window.jsmediatags;
				jsmediatags.read(location.origin+base_href+'$ilink', {
					onSuccess: function(tag) {
						var artist = tag.tags.artist,
						    title  = tag.tags.title;

						$('#audio-title').text(title);
						$('#audio-artist').text(artist);

						$('#audio-download').prop('download', (artist+' - '+title).substr(0, 250)+'.mp3');
					},
					onError: function(error) {
						console.log(error);
					}
				});
			</script>

			<p><a href='$ilink' id='audio-download'>Download</a>";

        $page->add_html_header("<script src='{$data_href}/ext/handle_mp3/lib/jsmediatags.min.js' type='text/javascript'></script>");
        $page->add_block(new Block("Music", $html, "main", 10));
    }
}
