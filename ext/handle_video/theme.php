<?php

class VideoFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$data_href = get_base_href();
		$ilink = $image->get_image_link();
		$thumb_url = make_http($image->get_thumb_link()); //used as fallback image
		$ext = strtolower($image->get_ext());
		$full_url = make_http($ilink);

		$html = "Video not playing? <a href='" . $image->parse_link_template(make_link('image/$id/$id%20-%20$tags.$ext')) . "'>Click here</a> to download the file.<br/>";

		//Browser media format support: https://developer.mozilla.org/en-US/docs/Web/HTML/Supported_media_formats
		$supportedExts = ['mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'ogv' => 'video/ogg', 'webm' => 'video/webm', 'flv' => 'video/flv'];
		if(array_key_exists($ext, $supportedExts)) {
			//FLV isn't supported by <video>, but it should always fallback to the flash-based method.
			if($ext == "webm") {
				//Several browsers still lack WebM support sadly: http://caniuse.com/#feat=webm
				$html .= "<!--[if IE]><p>To view webm files with IE, please <a href='https://tools.google.com/dlpage/webmmf/' target='_blank'>download this plugin</a>.</p><![endif]-->";
			}

			$html_fallback = "
						<object width=\"100%\" height=\"480px\" type=\"application/x-shockwave-flash\" data=\"lib/vendor/swf/flashmediaelement.swf\">
							<param name=\"movie\" value=\"lib/vendor/swf/flashmediaelement.swf\" />

							<param name=\"allowFullScreen\" value=\"true\" />
							<param name=\"wmode\" value=\"opaque\" />

							<param name=\"flashVars\" value=\"controls=true&autoplay=true&poster={$thumb_url}&file={$full_url}\" />
							<img src=\"{$thumb_url}\" />
						</object>";

			if($ext == "flv") {
				//FLV doesn't support <video>.
				$html .= $html_fallback;
			} else {
				$html .= "
					<video controls autoplay width=\"100%\">
						<source src='{$ilink}' type='{$supportedExts[$ext]}'>

						<!-- If browser doesn't support filetype, fallback to flash -->
						{$html_fallback}

					</video>";
			}

		} else {
			//This should never happen, but just in case let's have a fallback..
			$html = "Video type '$ext' not recognised";
		}
		$page->add_block(new Block("Video", $html, "main", 10));
	}
}

