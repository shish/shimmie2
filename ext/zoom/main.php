<?php

class Zoom extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			$page->add_main_block(new Block(null, $this->make_zoomer()));
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Image Zoom");
			$sb->add_bool_option("image_zoom", "Zoom by default: ");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool_from_post("image_zoom");
		}
	}

	private function make_zoomer() {
		global $config;
		$default = $config->get_bool("image_zoom", false) ? "scale(img);" : "";
		return <<<EOD
<script type="text/javascript">
img = byId("main_image");

img.onclick = function() {scale(img);};

msg_div = document.createElement("div");
msg_div.id = "msg_div";
msg_div.appendChild(document.createTextNode("Note: Image has been scaled to fit the screen; click to enlarge"));
msg_div.style.display="none";

img.parentNode.insertBefore(msg_div, img);

orig_width = "";

function scale(img) {
	if(img.style.width != "90%") {
		origwidth = img.style.width;
		img.style.width = "90%";
		msg_div.style.display = "block";
	}
	else {
		img.style.width = origwidth;
		msg_div.style.display = "none";
	}
}

$default
</script>
EOD;
	}
}
add_event_listener(new Zoom());
?>
