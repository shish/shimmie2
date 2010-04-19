<?php

class ZoomTheme extends Themelet {
	public function display_zoomer(Page $page, Image $image, $zoom_by_default) {
		$page->add_block(new Block(null, $this->make_zoomer($image->width, $zoom_by_default)));
	}

	protected function make_zoomer($image_width, $zoom_by_default) {
		global $config;
		$default = $zoom_by_default ? "scale(img);" : "";
		return <<<EOD
<!-- cancel border -->
<script type="text/javascript">
img = document.getElementById("main_image");

if(img) {
	img.onclick = function() {scale(img);};

	msg_div = document.createElement("div");
	msg_div.id = "msg_div";
	msg_div.appendChild(document.createTextNode("Note: Image has been scaled to fit the screen; click to enlarge"));
	msg_div.style.display="none";
	img.parentNode.insertBefore(msg_div, img);

	orig_width = $image_width;

	$default
}

function scale(img) {
	if(orig_width >= img.parentNode.clientWidth * 0.9) {
		if(img.style.width != "90%") {
			img.style.width = "90%";
			msg_div.style.display = "block";
		}
		else {
			img.style.width = orig_width + 'px';
			msg_div.style.display = "none";
		}
	}
}
</script>
EOD;
	}
}
?>
