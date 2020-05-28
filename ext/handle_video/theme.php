<?php declare(strict_types=1);

class VideoFileHandlerTheme extends Themelet
{

    const SUPPORTED_TYPES = [MIME_TYPE_MP4_VIDEO, MIME_TYPE_OGG_VIDEO, MIME_TYPE_WEBM, MIME_TYPE_FLASH_VIDEO];

    public function display_image(Page $page, Image $image)
    {
        global $config;
        $ilink = $image->get_image_link();
        $thumb_url = make_http($image->get_thumb_link()); //used as fallback image
        $ext = strtolower($image->get_ext());
        $full_url = make_http($ilink);
        $autoplay = $config->get_bool("video_playback_autoplay");
        $loop = $config->get_bool("video_playback_loop");
        $player = make_link('vendor/bower-asset/mediaelement/build/flashmediaelement.swf');

        $html = "Video not playing? <a href='$ilink'>Click here</a> to download the file.<br/>";

        //Browser media format support: https://developer.mozilla.org/en-US/docs/Web/HTML/Supported_media_formats
        $mime = get_mime_for_extension($ext);

        if (in_array($mime, self::SUPPORTED_TYPES)) {
            //FLV isn't supported by <video>, but it should always fallback to the flash-based method.
            if ($mime == MIME_TYPE_WEBM) {
                //Several browsers still lack WebM support sadly: https://caniuse.com/#feat=webm
                $html .= "<!--[if IE]><p>To view webm files with IE, please <a href='https://tools.google.com/dlpage/webmmf/' target='_blank'>download this plugin</a>.</p><![endif]-->";
            }

            $html_fallback = "
						<object width=\"100%\" height=\"480px\" type=\"application/x-shockwave-flash\" data=\"$player\">
							<param name=\"movie\" value=\"$player\" />

							<param name=\"allowFullScreen\" value=\"true\" />
							<param name=\"wmode\" value=\"opaque\" />

							<param name=\"flashVars\" value=\""
                                . "controls=true"
                                . "&autoplay=" . ($autoplay ? 'true' : 'false')
                                . "&poster={$thumb_url}"
                                . "&file={$full_url}"
                                . "&loop=" . ($loop ? 'true' : 'false') . "\" />
							<img alt='thumb' src=\"{$thumb_url}\" />
						</object>";

            if ($mime == MIME_TYPE_FLASH_VIDEO) {
                //FLV doesn't support <video>.
                $html .= $html_fallback;
            } else {
                $autoplay = ($autoplay ? ' autoplay' : '');
                $loop     = ($loop ? ' loop' : '');

                $html .= "
					<video controls class='shm-main-image' id='main_image' alt='main image' poster='$thumb_url' {$autoplay} {$loop}
					style='max-width: 100%'>
						<source src='{$ilink}' type='{$mime}'>

						<!-- If browser doesn't support filetype, fallback to flash -->
						{$html_fallback}
					</video>
					<script>$('#main_image').prop('volume', 0.25);</script>
				";
            }
        } else {
            //This should never happen, but just in case let's have a fallback..
            $html = "Video type '$mime' not recognised";
        }
        $page->add_block(new Block("Video", $html, "main", 10));
    }
}
