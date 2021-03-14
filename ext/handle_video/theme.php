<?php declare(strict_types=1);

class VideoFileHandlerTheme extends Themelet
{
    public function display_image(Page $page, Image $image)
    {
        global $config;
        $ilink = $image->get_image_link();
        $thumb_url = make_http($image->get_thumb_link()); //used as fallback image
        $mime = strtolower($image->get_mime());
        $autoplay = $config->get_bool(VideoFileHandlerConfig::PLAYBACK_AUTOPLAY);
        $loop = $config->get_bool(VideoFileHandlerConfig::PLAYBACK_LOOP);
        $mute = $config->get_bool(VideoFileHandlerConfig::PLAYBACK_MUTE);

        $width="auto";
        if ($image->width>1) {
            $width = $image->width."px";
        }
        $height="auto";
        if ($image->height>1) {
            $height = $image->height."px";
        }

        $html = "Video not playing? <a href='$ilink'>Click here</a> to download the file.<br/>";

        //Browser media format support: https://developer.mozilla.org/en-US/docs/Web/HTML/Supported_media_formats

        if (MimeType::matches_array($mime, VideoFileHandler::SUPPORTED_MIME)) {
            if ($mime == MimeType::WEBM) {
                //Several browsers still lack WebM support sadly: https://caniuse.com/#feat=webm
                $html .= "<!--[if IE]><p>To view webm files with IE, please <a href='https://tools.google.com/dlpage/webmmf/' target='_blank'>download this plugin</a>.</p><![endif]-->";
            }

            $autoplay = ($autoplay ? ' autoplay' : '');
            $loop     = ($loop ? ' loop' : '');
            $mute     = ($mute ? ' muted' : '');

            $html .= "
				<video controls class='shm-main-image' id='main_image' alt='main image' poster='$thumb_url' {$autoplay} {$loop} {$mute}
				style='height: $height; width: $width; max-width: 100%; object-fit: contain; background-color: black;'>
					<source src='{$ilink}' type='{$mime}'>
				</video>
				<script>$('#main_image').prop('volume', 0.25);</script>
			";
        } else {
            //This should never happen, but just in case let's have a fallback..
            $html = "Video type '$mime' not recognised";
        }
        $page->add_block(new Block("Video", $html, "main", 10));
    }
}
