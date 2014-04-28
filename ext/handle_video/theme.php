<?php

class VideoFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$data_href = get_base_href();
		$ilink = $image->get_image_link();
		$ext = strtolower($image->get_ext());
		
		if ($ext == "mp4") {
			$html = "Video not playing? <a href='" . $image->parse_link_template(make_link('image/$id/$id%20-%20$tags.$ext')) . "'>Click here</a> to download the file.<br/>
			<script language='JavaScript' type='text/javascript'>
if( navigator.userAgent.match(/Firefox/i) ||
	navigator.userAgent.match(/Opera/i) ||
	(navigator.userAgent.match(/MSIE/i) && parseFloat(navigator.appVersion.split('MSIE')[1]) < 9)){
		document.write(\"<object data='{$data_href}/lib/Jaris/bin/JarisFLVPlayer.swf' id='VideoPlayer' type='application/x-shockwave-flash' height='" . strval($image->height + 1). "px' width='" . strval($image->width) . "px'><param value='#000000' name='bgcolor'><param name='allowFullScreen' value='true'><param value='high' name='quality'><param value='opaque' name='wmode'><param value='source=$ilink&amp;type=video&amp;streamtype=file&amp;controltype=0' name='flashvars'></object>\");
}
else {	
		document.write(\"<video controls autoplay loop'>\");
		document.write(\"<source src='" . make_link("/image/" . $image->id) . "' type='video/mp4' />\");
		document.write(\"<object data='{$data_href}/lib/Jaris/bin/JarisFLVPlayer.swf' id='VideoPlayer' type='application/x-shockwave-flash' height='" . strval($image->height + 1). "px' width='" . strval($image->width) . "px'><param value='#000000' name='bgcolor'><param name='allowFullScreen' value='true'><param value='high' name='quality'><param value='opaque' name='wmode'><param value='source=$ilink&amp;type=video&amp;streamtype=file&amp;controltype=0' name='flashvars'></object>\");
}
</script>
<noscript>Javascript appears to be disabled. Please enable it and try again.</noscript>";
		} elseif ($ext == "flv") {
			$html = "Video not playing? <a href='" . $image->parse_link_template(make_link('image/$id/$id%20-%20$tags.$ext')) . "'>Click here</a> to download the file.<br/>
			<object data='{$data_href}/lib/Jaris/bin/JarisFLVPlayer.swf' id='VideoPlayer' type='application/x-shockwave-flash' height='" . strval($image->height + 1). "px' width='" . strval($image->width) . "px'>
			<param value='#000000' name='bgcolor'>
			<param name='allowFullScreen' value='true'>
			<param value='high' name='quality'>
			<param value='opaque' name='wmode'>
			<param value='source={$ilink}&amp;type=video&amp;streamtype=file&amp;controltype=0' name='flashvars'>
			</object>";
		} elseif ($ext == "ogv") {
			$html = "Video not playing? <a href='" . $image->parse_link_template(make_link('image/$id/$id%20-%20$tags.$ext')) . "'>Click here</a> to download the file.<br/>
			<video controls autoplay loop>
			<source src='" . make_link("/image/" . $image->id) . "' type='video/ogg' />
			</video>";
		} elseif ($ext == "webm") {
			$ie_only = "<!--[if IE]><p>To view webm files with IE, please <a href='http://tools.google.com/dlpage/webmmf/' target='_blank'>download this plugin</a>.</p><![endif]-->";
			$html = $ie_only ."Video not playing? <a href='" . $image->parse_link_template(make_link('image/$id/$id%20-%20$tags.$ext')) . "'>Click here</a> to download the file.<br/>
			<video controls autoplay loop>
			<source src='" . make_link("/image/" . $image->id) . "' type='video/webm' />
			</video>";
		}
		else {
			$html = "Video type '$ext' not recognised";
		}
		$page->add_block(new Block("Video", $html, "main", 10));
	}
}

