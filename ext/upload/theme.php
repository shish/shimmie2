<?php

class UploadTheme extends Themelet {
	public function display_block(Page $page) {
		$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_full(Page $page) {
		$page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "left", 20));
	}

	public function display_page(Page $page) {
		global $config;
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");
		
		// Uploader 2.0!
		$upload_list = "";
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
			$a=$i+1;
			$s=$i-1;
			if(!$i==0){
				$upload_list .="<tr id='row$i' style='display:none'>";
			}else{
				$upload_list .= "<tr id='row$i'>";
			}	
				$upload_list .= "<td width='15'>";
					
					if($i==0){
						$upload_list .= "<div id='hide$i'><img id='wrapper' src='ext/upload/minus.png' />" .
						"<a href='#' onclick='javascript:document.getElementById(&quot;row$a&quot;).style.display = &quot;&quot;;document.getElementById(&quot;hide$i&quot;).style.display = &quot;none&quot;;document.getElementById(&quot;hide$a&quot;).style.display = &quot;&quot;;'>".
						"<img src='ext/upload/plus.png'></a></div></td>";
					}else{
						$upload_list .="<div id='hide$i'>
						<a href='#' onclick='javascript:document.getElementById(&quot;row$i&quot;).style.display = &quot;none&quot;;".
						"document.getElementById(&quot;hide$i&quot;).style.display = &quot;none&quot;;".
						"document.getElementById(&quot;hide$s&quot;).style.display = &quot;&quot;;".
						"document.getElementById(&quot;data$i&quot;).value = &quot;&quot;;".
						"document.getElementById(&quot;url$i&quot;).value = &quot;&quot;;'>".
						"<img src='ext/upload/minus.png' /></a>";
						if($a==$config->get_int('upload_count')){
							$upload_list .="<img id='wrapper' src='ext/upload/plus.png' />";
							}else{
							$upload_list .=
							"<a href='#' onclick='javascript:document.getElementById(&quot;row$a&quot;).style.display = &quot;&quot;;".
							"document.getElementById(&quot;hide$i&quot;).style.display = &quot;none&quot;;".
							"document.getElementById(&quot;hide$a&quot;).style.display = &quot;&quot;;'>".
							"<img src='ext/upload/plus.png' /></a>";
							}
							$upload_list .= "</div></td>";
					}
					
					$upload_list .=
					"<td width='60'><form><input id='radio_buttona' type='radio' name='method' value='file' checked='checked' onclick='javascript:document.getElementById(&quot;url$i&quot;).style.display = &quot;none&quot;;document.getElementById(&quot;url$i&quot;).value = &quot;&quot;;document.getElementById(&quot;data$i&quot;).style.display = &quot;&quot;' /> File<br>";
				if($tl_enabled) {
					$upload_list .=
					"<input id='radio_buttonb' type='radio' name='method' value='url' onclick='javascript:document.getElementById(&quot;data$i&quot;).style.display = &quot;none&quot;;document.getElementById(&quot;data$i&quot;).value = &quot;&quot;;document.getElementById(&quot;url$i&quot;).style.display = &quot;&quot;' /> URL</ br></td></form>
					
					<td><input id='data$i' name='data$i' class='wid' type='file'><input id='url$i' name='url$i' class='wid' type='text' style='display:none'></td>
					";
					}
					else { 
					$upload_list .= "</form></td>
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
			".make_form(make_link("upload"), "POST", $multipart=True)."
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
			if($config->get_bool('nice_urls')){
				$delimiter = '?';
			} else {
				$delimiter = '&amp;';
			}
				{
			$title = "Upload to " . $config->get_string('title');
			$html .= '<p><a href="javascript:location.href=&quot;' .
				$link . $delimiter . 'url=&quot;+location.href+&quot;&amp;tags=&quot;+prompt(&quot;enter tags&quot;)">' .
				$title . '</a> (Drag & drop onto your bookmarks toolbar, then click when looking at an image)';
			}
				{
			/* Danbooru > Shimmie Bookmarklet.
				This "should" work on any site running danbooru, unless for some odd reason they switched around the id's or aren't using post/list.
				Most likely this will stop working when Danbooru updates to v2, all depends if they switch the ids or not >_>.
				Clicking the link on a danbooru image page should give you something along the lines of:
				'http://www.website.com/shimmie/upload?url="http://sonohara.donmai.us/data/crazylongurl.jpg&tags="too many tags"&rating="s"&source="http://danbooru.donmai.us/post/show/012345/"'
				TODO: Possibly make the entire/most of the script into a .js file, and just make the bookmarklet load it on click (Something like that?)
			*/
			$title = "Danbooru to " . $config->get_string('title');
			$html .= '<p><a href="javascript:'.
				/* This should stop the bookmarklet being insanely long...not that it's already huge or anything. */
				'var ste=&quot;'. $link . $delimiter .'url=&quot;;var tag=document.getElementById(&quot;post_tags&quot;).value;var rtg=document.documentElement.innerHTML.match(&quot;<li>Rating: (.*)<\/li>&quot;);var srx=&quot;http://&quot; + document.location.hostname+document.location.href.match(&quot;\/post\/show\/.*\/&quot;);' .
				//The default confirm sucks, mainly due to being unable to change the text in the Ok/Cancel box (Yes/No would be better.)
				'if (confirm(&quot;OK = Use Current tags.\nCancel = Use new tags.&quot;)==true){' . //Just incase some people don't want the insane amount of tags danbooru has.
					//The flash check is kind of picky, although it should work on "most" images..there will be either some old or extremely new ones that lack the flash tag.
					'if(tag.search(/\bflash\b/)==-1){'.
						'location.href=ste+document.getElementById(&quot;highres&quot;).href+&quot;&amp;tags=&quot;+tag+&quot;&amp;rating=&quot;+rtg[1]+&quot;&amp;source=&quot;+srx;}'.
					'else{'.
						'location.href=ste+document.getElementsByName(&quot;movie&quot;)[0].value+&quot;&amp;tags=&quot;+tag+&quot;&amp;rating=&quot;+rtg[1]+&quot;&amp;source=&quot;+srx;}'.
				//The following is more or less the same as above, instead using the tags on danbooru, should load a prompt box instead.
				'}else{'.
					'var p=prompt(&quot;Enter Tags&quot;,&quot;&quot;);'.
					'if(tag.search(/\bflash\b/)==-1){'.
						'location.href=ste+document.getElementById(&quot;highres&quot;).href+&quot;&amp;tags=&quot;+p+&quot;&amp;rating=&quot;+rtg[1]+&quot;&amp;source=&quot;+srx;}' .
					'else{'.
						'location.href=ste+document.getElementsByName(&quot;movie&quot;)[0].value+&quot;&amp;tags=&quot;+p+&quot;&amp;rating=&quot;+rtg[1]+&quot;&amp;source=&quot;+srx;}'.
				'}">' .
				$title . '</a> (As above, Click on a Danbooru-run image page. (This also grabs the tags/rating/source!))';

			}
				
		}

		$page->set_title("Upload");
		$page->set_heading("Upload");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Upload", $html, "main", 20));
	}

	/* only allows 1 file to be uploaded - for replacing another image file */
	public function display_replace_page(Page $page, $image_id) {
		global $config;
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");

		$upload_list = '';
		$upload_list .= "
				<tr>
					<td width='60'><form><input id='radio_buttona' type='radio' name='method' value='file' checked='checked' onclick='javascript:document.getElementById(&quot;url0&quot;).style.display = &quot;none&quot;;document.getElementById(&quot;url0&quot;).value = &quot;&quot;;document.getElementById(&quot;data0&quot;).style.display = &quot;&quot;' /> File<br>";
				if($tl_enabled) {
					$upload_list .="
					<input id='radio_buttonb' type='radio' name='method' value='url' onclick='javascript:document.getElementById(&quot;data0&quot;).style.display = &quot;none&quot;;document.getElementById(&quot;data0&quot;).value = &quot;&quot;;document.getElementById(&quot;url0&quot;).style.display = &quot;&quot;' /> URL</ br></td></form>
					<td><input id='data0' name='data0' class='wid' type='file'><input id='url0' name='url0' class='wid' type='text' style='display:none'></td>
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
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
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