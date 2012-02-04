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
		$page->add_html_header("<link rel='stylesheet' href='".get_base_href()."/ext/upload/_style.css' type='text/css'>");
		
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");
		// Uploader 2.0!
		$upload_list = "";
		$upload_count = $config->get_int('upload_count');
		
		for($i=0; $i<$upload_count; $i++)
		{
			$a=$i+1;
			$s=$i-1;
			
			if(!$i==0){
				$upload_list .="<tr id='row$i' style='display:none'>";
			}else{
				$upload_list .= "<tr id='row$i'>";
			}
			
			$upload_list .= "<td width='15'>";
					
			if($i==0){
				$js = 'javascript:$(function() {
					$("#row'.$a.'").show();
					$("#hide'.$i.'").hide();
					$("#hide'.$a.'").show();});';
				
				$upload_list .= "<div id='hide$i'><img id='wrapper' src='ext/upload/minus.png' />" .
				"<a href='#' onclick='$js'>".
				"<img src='ext/upload/plus.png'></a></div></td>";
			} else {
				$js = 'javascript:$(function() {
				$("#row'.$i.'").hide();
				$("#hide'.$i.'").hide();
				$("#hide'.$s.'").show();
				$("#data'.$i.'").val("");
				$("#url'.$i.'").val("");
				});';
				
				$upload_list .="<div id='hide$i'>
				<a href='#' onclick='$js'>".
				"<img src='ext/upload/minus.png' /></a>";
				
				if($a==$config->get_int('upload_count')){
					$upload_list .="<img id='wrapper' src='ext/upload/plus.png' />";
				}else{
					$js1 = 'javascript:$(function() {
						$("#row'.$a.'").show();
						$("#hide'.$i.'").hide();
						$("#hide'.$a.'").show(); });';
						
					$upload_list .=
					"<a href='#' onclick='$js1'>".
					"<img src='ext/upload/plus.png' /></a>";
				}
				$upload_list .= "</div></td>";
			}
					
			$js2 = 'javascript:$(function() {
						$("#url'.$i.'").hide();
						$("#url'.$i.'").val("");
						$("#data'.$i.'").show(); });';

			$upload_list .=
			"<form><td width='60'><input id='radio_button_a$i' type='radio' name='method' value='file' checked='checked' onclick='$js2' /> File<br>";
			
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
		
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		$html = "
			<script type='text/javascript'>
			$(document).ready(function() {
				$('#tag_box').DefaultValue('tagme');
				$('#tag_box').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			</script>
			".make_form(make_link("upload"), "POST", $multipart=True, 'file_upload')."
				<table id='large_upload_form' class='vert'>
					$upload_list
					<tr><td></td><td>Tags<td colspan='3'><input id='tag_box' name='tags' type='text'></td></tr>
					<tr><td></td><td>Source</td><td colspan='3'><input name='source' type='text'></td></tr>
					<tr><td colspan='4'><input id='uploadbutton' type='submit' value='Post'></td></tr>
				</table>
			</form>
			<small>(Max file size is $max_kb)</small>
		";
		
		if($tl_enabled) {
			$link = make_http(make_link("upload"));
			$main_page = make_http(make_link());
			$title = $config->get_string('title');
			
			if($config->get_bool('nice_urls')){
				$delimiter = '?';
			} else {
				$delimiter = '&amp;';
			}
			{
				$js='javascript:(function(){if(typeof window=="undefined"||!window.location||window.location.href=="about:blank"){window.location="'. $main_page .'";}else if(typeof document=="undefined"||!document.body){window.location="'. $main_page .'?url="+encodeURIComponent(window.location.href);} else if(window.location.href.match("\/\/'. $_SERVER["HTTP_HOST"] .'.*")){alert("You are already at '. $title .'!");} else{var tags=prompt("Please enter tags","tagme");if(tags!=""&&tags!=null){var link="'. $link . $delimiter .'url="+location.href+"&tags="+tags;var w=window.open(link,"_blank");}}})();';
				$html .= '<p><a href=\''.$js.'\'>Upload to '.$title.'</a> (Drag &amp; drop onto your bookmarks toolbar, then click when looking at an image)';
			}
				{
			/* Imageboard > Shimmie Bookmarklet
				This is more or less, an upgraded version of the "Danbooru>Shimmie" bookmarklet.
				At the moment this is known to work with Shimmie/Danbooru/Gelbooru/oreno.imouto/konachan/sankakucomplex.
				The bookmarklet is now also loaded via the .js file in this folder.
			*/
			//Bookmarklet checks if shimmie supports ext. If not, won't upload to site/shows alert saying not supported.
			$supported_ext = "jpg jpeg gif png";
			if(file_exists("ext/handle_flash")){$supported_ext .= " swf";}
			if(file_exists("ext/handle_ico")){$supported_ext .= " ico ani cur";}
			if(file_exists("ext/handle_mp3")){$supported_ext .= " mp3";}
			if(file_exists("ext/handle_svg")){$supported_ext .= " svg";}
			$title = "Booru to " . $config->get_string('title');
			//CA=0: Ask to use current or new tags | CA=1: Always use current tags | CA=2: Always use new tags
			$html .= '<p><a href="javascript:var ste=&quot;'. $link . $delimiter .'url=&quot;; var supext=&quot;'.$supported_ext.'&quot;; var maxsze=&quot;'.$max_kb.'&quot;; var CA=0; void(document.body.appendChild(document.createElement(&quot;script&quot;)).src=&quot;'.make_http(get_base_href())."/ext/upload/bookmarklet.js".'&quot;)">'.
				$title . '</a> (Click when looking at an image page. Works on sites running Shimmie/Danbooru/Gelbooru. (This also grabs the tags/rating/source!))';
			}
				
		}

		$page->set_title("Upload");
		$page->set_heading("Upload");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Upload", $html, "main", 20));
	}

	/* only allows 1 file to be uploaded - for replacing another image file */
	public function display_replace_page(Page $page, $image_id) {
		global $config, $page;
		$page->add_html_header("<link rel='stylesheet' href='".get_base_href()."/ext/upload/_style.css' type='text/css'>");
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");

		$js2 = 'javascript:$(function() {
			$("#data").hide();
			$("#data").val("");
			$("#url").show(); });';

		$js1 = 'javascript:$(function() {
			$("#url").hide();
			$("#url").val("");
			$("#data").show(); });';

		$upload_list = '';
		$upload_list .= "
				<tr>
					<td width='60'><form><input id='radio_button_a' type='radio' name='method' value='file' checked='checked' onclick='$js1' /> File<br>";
				if($tl_enabled) {
					$upload_list .="
					<input id='radio_button_b' type='radio' name='method' value='url' onclick='$js2' /> URL</ br></td></form>
					<td><input id='data' name='data' class='wid' type='file'><input id='url' name='url' class='wid' type='text' style='display:none'></td>
					";
				} else { 
					$upload_list .= "</form></td>
					";
				}

		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		
		$image = Image::by_id($image_id);
		$thumbnail = $this->build_thumb_html($image, null);
		
		$html = "
				<div style='clear:both;'></div>
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
	
	public function display_upload_status(Page $page, $ok) {
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

	public function display_upload_error(Page $page, $title, $message) {
		$page->add_block(new Block($title, $message));
	}

	protected function build_upload_block() {
		global $config;

		$upload_list = "";
		$upload_count = $config->get_int('upload_count');
		
		for($i=0; $i<$upload_count; $i++) {
			if($i == 0) $style = ""; // "style='display:visible'";
			else $style = "style='display:none'";
			$upload_list .= "<input size='10' ".
				"id='data$i' name='data$i' $style onchange=\"$('#data".($i+1)."').show()\" type='file'>\n";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		// <input type='hidden' name='max_file_size' value='$max_size' />
		return "
			<script type='text/javascript'>
			$(document).ready(function() {
				$('#tag_input').DefaultValue('tagme');
				$('#tag_input').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			</script>
			".make_form(make_link("upload"), "POST", $multipart=True)."
				$upload_list
				<input id='tag_input' name='tags' type='text' autocomplete='off'>
				<input type='submit' value='Post'>
			</form>
			<div id='upload_completions' style='clear: both;'><small>(Max file size is $max_kb)</small></div>
			<noscript><a href='".make_link("upload")."'>Larger Form</a></noscript>
		";
	}
}
?>