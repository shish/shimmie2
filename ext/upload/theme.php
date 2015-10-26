<?php

class UploadTheme extends Themelet {
	public function display_block(Page $page) {
		$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_full(Page $page) {
		$page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "left", 20));
	}

	public function display_page(Page $page) {
		global $config, $page;

		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		$upload_list = $this->h_upload_list_1();
		$html = "
			".make_form(make_link("upload"), "POST", $multipart=True, 'file_upload')."
				<table id='large_upload_form' class='vert'>
					$upload_list
					<tr><td width='20'>Tags<td colspan='5'><input name='tags' type='text' placeholder='tagme' class='autocomplete_tags' autocomplete='off'></td></tr>
					<tr><td>Source</td><td colspan='5'><input name='source' type='text'></td></tr>
					<tr><td colspan='6'><input id='uploadbutton' type='submit' value='Post'></td></tr>
				</table>
			</form>
			<small>(Max file size is $max_kb)</small>
		";
		
		$page->set_title("Upload");
		$page->set_heading("Upload");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Upload", $html, "main", 20));
		if($tl_enabled) {
			$page->add_block(new Block("Bookmarklets", $this->h_bookmarklets(), "left", 20));
		}
	}

	/**
	 * @return string
	 */
	protected function h_upload_list_1() {
		global $config;
		$upload_list = "";
		$upload_count = $config->get_int('upload_count');
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");

		if($tl_enabled) {
			$upload_list .= "
				<tr>
					<td colspan='2'>Files</td>
					<td colspan='2'>URLs</td>
					<td colspan='2'>Image-Specific Tags</td>
				</tr>
			";

			for($i=0; $i<$upload_count; $i++) {
				$upload_list .= "
					<tr>
						<td colspan='2'><input type='file' name='data$i'></td>
						<td colspan='2'><input type='text' name='url$i'</td>
						<td colspan='2'><input type='text' name='tags$i' class='autocomplete_tags' autocomplete='off'></td>
					</tr>
				";
			}
		}
		else {
			$upload_list .= "
				<tr>
					<td colspan='4'>Files</td>
					<td colspan='2'>Image-Specific Tags</td>
				</tr>
			";

			for($i=0; $i<$upload_count; $i++) {
				$upload_list .= "
					<tr>
						<td colspan='4'><input type='file' name='data$i'></td>
						<td colspan='2'><input type='text' name='tags$i' class='autocomplete_tags' autocomplete='off'></td>
					</tr>
				";
			}
		}

		return $upload_list;
	}

	/**
	 * @return string
	 */
	protected function h_upload_List_2() {
		global $config;

		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");
		// Uploader 2.0!
		$upload_list = "";
		$upload_count = $config->get_int('upload_count');
		
		for($i=0; $i<$upload_count; $i++) {
			$a = $i+1;
			$s = $i-1;
			
			if($i != 0) {
				$upload_list .="<tr id='row$i' style='display:none'>";
			}else{
				$upload_list .= "<tr id='row$i'>";
			}
			
			$upload_list .= "<td width='15'>";
					
			if($i == 0) {
				$js = 'javascript:$(function() {
					$("#row'.$a.'").show();
					$("#hide'.$i.'").hide();
					$("#hide'.$a.'").show();});';
				
				$upload_list .= "
					<div id='hide$i'>
						<img id='wrapper' src='ext/upload/minus.png' />
						<a href='#' onclick='$js'><img src='ext/upload/plus.png'></a>
					</div>
				";
			} else {
				$js = 'javascript:$(function() {
				$("#row'.$i.'").hide();
				$("#hide'.$i.'").hide();
				$("#hide'.$s.'").show();
				$("#data'.$i.'").val("");
				$("#url'.$i.'").val("");
				});';
				
				$upload_list .="
					<div id='hide$i'>
						<a href='#' onclick='$js'><img src='ext/upload/minus.png' /></a>
				";
				
				if($a == $upload_count){
					$upload_list .="<img id='wrapper' src='ext/upload/plus.png' />";
				}
				else{
					$js1 = 'javascript:$(function() {
						$("#row'.$a.'").show();
						$("#hide'.$i.'").hide();
						$("#hide'.$a.'").show(); });';
						
					$upload_list .=
					"<a href='#' onclick='$js1'>".
					"<img src='ext/upload/plus.png' /></a>";
				}
				$upload_list .= "</div>";
			}
			$upload_list .= "</td>";
					
			$js2 = 'javascript:$(function() {
						$("#url'.$i.'").hide();
						$("#url'.$i.'").val("");
						$("#data'.$i.'").show(); });';

			$upload_list .= "
				<form><td width='60'><input id='radio_button_a$i' type='radio' name='method' value='file' checked='checked' onclick='$js2' /> File<br>";
			
			if($tl_enabled) {
				$js = 'javascript:$(function() {
						$("#data'.$i.'").hide();
						$("#data'.$i.'").val("");
						$("#url'.$i.'").show(); });';
				
				$upload_list .= 
				"<input id='radio_button_b$i' type='radio' name='method' value='url' onclick='$js' /> URL</ br></td></form>
				<td>
					<input id='data$i' name='data$i' class='wid' type='file'>
					<input id='url$i' name='url$i' class='wid' type='text' style='display:none'>
				</td>";
			} else {
				$upload_list .= "</td>
				<td width='250'><input id='data$i' name='data$i' class='wid' type='file'></td>
				";
			}
					
			$upload_list .= "
				</tr>
			";
		}

		return $upload_list;
	}

	/**
	 * @return string
	 */
	protected function h_bookmarklets() {
		global $config;
		$link = make_http(make_link("upload"));
		$main_page = make_http(make_link());
		$title = $config->get_string('title');
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		$delimiter = $config->get_bool('nice_urls') ? '?' : '&amp;';
		$html = '';

		$js='javascript:(
			function() {
				if(typeof window=="undefined" || !window.location || window.location.href=="about:blank") {
					window.location = "'. $main_page .'";
				}
				else if(typeof document=="undefined" || !document.body) {
					window.location = "'. $main_page .'?url="+encodeURIComponent(window.location.href);
				}
				else if(window.location.href.match("\/\/'. $_SERVER["HTTP_HOST"] .'.*")) {
					alert("You are already at '. $title .'!");
				}
				else {
					var tags = prompt("Please enter tags", "tagme");
					if(tags != "" && tags != null) {
						var link = "'. $link . $delimiter .'url="+location.href+"&tags="+tags;
						var w = window.open(link, "_blank");
					}
				}
			}
		)();';
		$html .= '<a href=\''.$js.'\'>Upload to '.$title.'</a>';
		$html .= ' (Drag &amp; drop onto your bookmarks toolbar, then click when looking at an image)';

		// Bookmarklet checks if shimmie supports ext. If not, won't upload to site/shows alert saying not supported.
		$supported_ext = "jpg jpeg gif png";
		if(class_exists("FlashFileHandler")){$supported_ext .= " swf";}
		if(class_exists("ICOFileHandler")){$supported_ext .= " ico ani cur";}
		if(class_exists("MP3FileHandler")){$supported_ext .= " mp3";}
		if(class_exists("SVGFileHandler")){$supported_ext .= " svg";}
		$title = "Booru to " . $config->get_string('title');
		// CA=0: Ask to use current or new tags | CA=1: Always use current tags | CA=2: Always use new tags
		$html .= '<p><a href="javascript:
			var ste=&quot;'. $link . $delimiter .'url=&quot;;
			var supext=&quot;'.$supported_ext.'&quot;;
			var maxsize=&quot;'.$max_kb.'&quot;;
			var CA=0;
			void(document.body.appendChild(document.createElement(&quot;script&quot;)).src=&quot;'.make_http(get_base_href())."/ext/upload/bookmarklet.js".'&quot;)
		">'. $title . '</a> (Click when looking at an image page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!))';

		return $html;
	}

	/**
	 * Only allows 1 file to be uploaded - for replacing another image file.
	 *
	 * @param Page $page
	 * @param int $image_id
	 */
	public function display_replace_page(Page $page, /*int*/ $image_id) {
		global $config, $page;
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");

		$upload_list = "
				<tr>
					<td>File</td>
					<td><input name='data' type='file'></td>
					";
		if($tl_enabled) {
			$upload_list .="
			<td>or URL</td>
			<td><input name='url' type='text'></td>
			";
		}
		$upload_list .= "</tr>";

		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		
		$image = Image::by_id($image_id);
		$thumbnail = $this->build_thumb_html($image);
		
		$html = "
				<p>Replacing Image ID ".$image_id."<br>Please note: You will have to refresh the image page, or empty your browser cache.</p>"
				.$thumbnail."<br>"
				.make_form(make_link("upload/replace/".$image_id), "POST", $multipart=True)."
				<input type='hidden' name='image_id' value='$image_id'>
				<table id='large_upload_form' class='vert'>
					$upload_list
					<tr><td>Source</td><td colspan='3'><input name='source' type='text'></td></tr>
					<tr><td colspan='4'><input id='uploadbutton' type='submit' value='Post'></td></tr>
				</table>
			</form>
			<small>(Max file size is $max_kb)</small>
		";

		$page->set_title("Replace Image");
		$page->set_heading("Replace Image");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Upload Replacement Image", $html, "main", 20));
	}

	/**
	 * @param Page $page
	 * @param bool $ok
	 */
	public function display_upload_status(Page $page, /*bool*/ $ok) {
		if($ok) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link());
		}
		else {
			$page->set_title("Upload Status");
			$page->set_heading("Upload Status");
			$page->add_block(new NavBlock());
		}
	}

	/**
	 * @param Page $page
	 * @param string $title
	 * @param string $message
	 */
	public function display_upload_error(Page $page, /*string*/ $title, /*string*/ $message) {
		$page->add_block(new Block($title, $message));
	}

	/**
	 * @return string
	 */
	protected function build_upload_block() {
		global $config;

		$upload_list = "";
		$upload_count = $config->get_int('upload_count');
		
		for($i=0; $i<$upload_count; $i++) {
			if($i == 0) $style = ""; // "style='display:visible'";
			else $style = "style='display:none'";
			$upload_list .= "<input id='data$i' name='data$i' $style onchange=\"$('#data".($i+1)."').show()\" size='16' type='file'>\n";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		// <input type='hidden' name='max_file_size' value='$max_size' />
		return "
			<div class='mini_upload'>
			".make_form(make_link("upload"), "POST", $multipart=True)."
				$upload_list
				<input name='tags' type='text' placeholder='tagme' class='autocomplete_tags' required='required' autocomplete='off'>
				<input type='submit' value='Post'>
			</form>
			<small>(Max file size is $max_kb)</small>
			<noscript><br><a href='".make_link("upload")."'>Larger Form</a></noscript>
			</div>
		";
	}
}

