<?php

class PixelFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		global $config;

		$u_ilink = $image->get_image_link();
		$html = "<img alt='main image' id='main_image' src='$u_ilink'>";
		if($config->get_bool("image_show_meta") && function_exists("exif_read_data")) {
			# FIXME: only read from jpegs?
			$exif = @exif_read_data($image->get_image_filename(), 0, true);
			if($exif) {
				$head = "";
				foreach ($exif as $key => $section) {
					foreach ($section as $name => $val) {
						if($key == "IFD0") {
							$head .= html_escape("$name: $val")."<br>\n";
						}
					}
				}
				if($head) {
					$page->add_block(new Block("EXIF Info", $head, "left"));
				}
			}
		}

		$zoom_default = $config->get_bool("image_zoom", false) ? "scale(img);" : "";
		$zoom = "<script type=\"text/javascript\">
					img = document.getElementById(\"main_image\");
					
					if(img) {
						img.onclick = function() {scale(img);};
					
						msg_div = document.createElement(\"div\");
						msg_div.id = \"msg_div\";
						msg_div.appendChild(document.createTextNode(\"Note: Image has been scaled to fit the screen; click to enlarge\"));
						msg_div.style.display=\"none\";
						img.parentNode.insertBefore(msg_div, img);
					
						orig_width = $image->width;
					
						$zoom_default
					}
					
					function scale(img) {
						if(orig_width >= img.parentNode.clientWidth * 0.9) {
							if(img.style.width != \"90%\") {
								img.style.width = \"90%\";
								msg_div.style.display = \"block\";
							}
							else {
								img.style.width = orig_width + 'px';
								msg_div.style.display = \"none\";
							}
						}
					}
				</script>";
		
		$page->add_block(new Block("Image", $html.$zoom, "main", 10));
	}
}
?>
