<?php

class VideoFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$data_href = get_base_href();
		$ilink = $image->get_image_link();
		$ext = strtolower($image->get_ext());
		
		if ($ext == "mp4") {
			$html = "Video not playing? <a href='" . make_link("/image/" . $image->id) . "'>Click here</a> to download the file.<br><script language='JavaScript' type='text/javascript'>
if( navigator.userAgent.match(/Firefox/i) ||
	navigator.userAgent.match(/Opera/i) ||
	(navigator.userAgent.match(/MSIE/i) && parseFloat(navigator.appVersion.split('MSIE')[1]) < 9)){
		document.write(\"<object data='$data_href/lib/Jaris/bin/JarisFLVPlayer.swf' id='VideoPlayer' type='application/x-shockwave-flash' height='" . strval($image->height + 1). "px' width='" . strval($image->width) . "px'><param value='#000000' name='bgcolor'><param name='allowFullScreen' value='true'><param value='high' name='quality'><param value='opaque' name='wmode'><param value='source=$ilink&amp;type=video&amp;streamtype=file&amp;controltype=0' name='flashvars'></object>\");
}
else {	
		document.write(\"<video controls='controls' autoplay='autoplay'>\");
		document.write(\"<source src='" . make_link("/image/" . $image->id) . "' type='video/mp4' />\");
		document.write(\"<object data='$data_href/lib/Jaris/bin/JarisFLVPlayer.swf' id='VideoPlayer' type='application/x-shockwave-flash' height='" . strval($image->height + 1). "px' width='" . strval($image->width) . "px'><param value='#000000' name='bgcolor'><param name='allowFullScreen' value='true'><param value='high' name='quality'><param value='opaque' name='wmode'><param value='source=$ilink&amp;type=video&amp;streamtype=file&amp;controltype=0' name='flashvars'></object>\");
}
</script>
<noscript>Javascript appears to be disabled. Please enable it and try again.</noscript>";
		} elseif ($ext == "flv") {
			$html = "Video not playing? <a href='" . make_link("/image/" . $image->id) . "'>Click here</a> to download the file.<br><object data='$data_href/lib/Jaris/bin/JarisFLVPlayer.swf' id='VideoPlayer' type='application/x-shockwave-flash' height='" . strval($image->height + 1). "px' width='" . strval($image->width) . "px'><param value='#000000' name='bgcolor'><param name='allowFullScreen' value='true'><param value='high' name='quality'><param value='opaque' name='wmode'><param value='source=$ilink&amp;type=video&amp;streamtype=file&amp;controltype=0' name='flashvars'></object>";
		} elseif ($ext == "ogv") {
			$html = "Video not playing? <a href='" . make_link("/image/" . $image->id) . "'>Click here</a> to download the file.<br><video controls='controls' autoplay='autoplay'>
			<source src='" . make_link("/image/" . $image->id) . "' type='video/ogg' /><br>
			</video>";
		} elseif ($ext == "webm") {
			$html = "Video not playing? <a href='" . make_link("/image/" . $image->id) . "'>Click here</a> to download the file.<br><video controls='controls' autoplay='autoplay'>
			<source src='" . make_link("/image/" . $image->id) . "' type='video/webm' /><br>
			</video>";
		}
		$page->add_block(new Block("Video", $html, "main", 10));
	}
}
?>
