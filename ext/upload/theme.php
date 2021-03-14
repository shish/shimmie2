<?php declare(strict_types=1);

class UploadTheme extends Themelet
{
    protected bool $has_errors = false;

    public function display_block(Page $page)
    {
        $b = new Block("Upload", $this->build_upload_block(), "left", 20);
        $b->is_content = false;
        $page->add_block($b);
    }

    public function display_full(Page $page)
    {
        $page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "left", 20));
    }

    public function display_page(Page $page)
    {
        global $config, $page;

        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);
        $upload_list = $this->h_upload_list_1();
        $html = "
			".make_form(make_link("upload"), "POST", $multipart=true, 'file_upload')."
				<table id='large_upload_form' class='vert'>
					<tr><td width='20'>Common&nbsp;Tags<td colspan='5'><input name='tags' type='text' placeholder='tagme' class='autocomplete_tags' autocomplete='off'></td></tr>
					<tr><td>Common&nbsp;Source</td><td colspan='5'><input name='source' type='text'></td></tr>
					$upload_list
					<tr><td colspan='6'><input id='uploadbutton' type='submit' value='Post'></td></tr>
				</table>
			</form>
			<small>(Max file size is $max_kb)</small>
		";

        $page->set_title("Upload");
        $page->set_heading("Upload");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload", $html, "main", 20));
        if ($tl_enabled) {
            $page->add_block(new Block("Bookmarklets", $this->h_bookmarklets(), "left", 20));
        }
    }

    protected function h_upload_list_1(): string
    {
        global $config;
        $upload_list = "";
        $upload_count = $config->get_int(UploadConfig::COUNT);
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        if ($tl_enabled) {
            $upload_list .= "
				<tr>
					<td colspan='2'>Files</td>
					<td colspan='2'>URLs</td>
					<td colspan='2'>Post-Specific Tags</td>
				</tr>
			";

            for ($i=0; $i<$upload_count; $i++) {
                $upload_list .= "
					<tr>
						<td colspan='2'><input type='file' name='data${i}[]' accept='$accept' multiple></td>
						<td colspan='2'><input type='text' name='url$i'></td>
						<td colspan='2'><input type='text' name='tags$i' class='autocomplete_tags' autocomplete='off'></td>
					</tr>
				";
            }
        } else {
            $upload_list .= "
				<tr>
					<td colspan='4'>Files</td>
					<td colspan='2'>Post-Specific Tags</td>
				</tr>
			";

            for ($i=0; $i<$upload_count; $i++) {
                $upload_list .= "
					<tr>
						<td colspan='4'><input type='file' name='data${i}[]' accept='$accept' multiple></td>
						<td colspan='2'><input type='text' name='tags$i' class='autocomplete_tags' autocomplete='off'></td>
					</tr>
				";
            }
        }

        return $upload_list;
    }

    protected function h_bookmarklets(): string
    {
        global $config;
        $link = make_http(make_link("upload"));
        $main_page = make_http(make_link());
        $title = $config->get_string(SetupConfig::TITLE);
        $max_size = $config->get_int(UploadConfig::SIZE);
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
        $html .= ' (Drag &amp; drop onto your bookmarks toolbar, then click when looking at a post)';

        // Bookmarklet checks if shimmie supports ext. If not, won't upload to site/shows alert saying not supported.
        $supported_ext = join(" ", DataHandlerExtension::get_all_supported_exts());

        $title = "Booru to " . $config->get_string(SetupConfig::TITLE);
        // CA=0: Ask to use current or new tags | CA=1: Always use current tags | CA=2: Always use new tags
        $html .= '<p><a href="javascript:
			var ste=&quot;'. $link . $delimiter .'url=&quot;;
			var supext=&quot;'.$supported_ext.'&quot;;
			var maxsize=&quot;'.$max_kb.'&quot;;
			var CA=0;
			void(document.body.appendChild(document.createElement(&quot;script&quot;)).src=&quot;'.make_http(get_base_href())."/ext/upload/bookmarklet.js".'&quot;)
		">'. $title . '</a> (Click when looking at a post page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!))';

        return $html;
    }

    /**
     * Only allows 1 file to be uploaded - for replacing another image file.
     */
    public function display_replace_page(Page $page, int $image_id)
    {
        global $config, $page;
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        $upload_list = "
			<tr>
				<td>File</td>
				<td><input name='data[]' type='file' accept='$accept'></td>
			</tr>
		";
        if ($tl_enabled) {
            $upload_list .="
			<tr>
				<td>or URL</td>
				<td><input name='url' type='text'></td>
			</tr>
			";
        }

        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);

        $image = Image::by_id($image_id);
        $thumbnail = $this->build_thumb_html($image);

        $html = "
				<p>Replacing Post ID ".$image_id."<br>Please note: You will have to refresh the post page, or empty your browser cache.</p>"
                .$thumbnail."<br>"
                .make_form(make_link("upload/replace/".$image_id), "POST", $multipart=true)."
				<input type='hidden' name='image_id' value='$image_id'>
				<table id='large_upload_form' class='vert'>
					$upload_list
					<tr><td>Source</td><td colspan='3'><input name='source' type='text'></td></tr>
					<tr><td colspan='4'><input id='uploadbutton' type='submit' value='Post'></td></tr>
				</table>
			</form>
			<small>(Max file size is $max_kb)</small>
		";

        $page->set_title("Replace Post");
        $page->set_heading("Replace Post");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload Replacement Post", $html, "main", 20));
    }

    public function display_upload_status(Page $page, array $image_ids)
    {
        global $user;

        if ($this->has_errors) {
            $page->set_title("Upload Status");
            $page->set_heading("Upload Status");
            $page->add_block(new NavBlock());
        } else {
            if (count($image_ids) < 1) {
                $page->set_title("No images uploaded");
                $page->set_heading("No images uploaded");
                $page->add_block(new NavBlock());
            } elseif (count($image_ids) == 1) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/view/{$image_ids[0]}"));
            } else {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/list/poster={$user->name}/1"));
            }
        }
    }

    public function display_upload_error(Page $page, string $title, string $message)
    {
        // this message has intentional HTML in it...
        $message = str_contains($message, "already has hash") ? $message : html_escape($message);
        $page->add_block(new Block($title, $message));
        $this->has_errors = true;
    }

    protected function build_upload_block(): string
    {
        global $config;

        $accept = $this->get_accept();

        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);

        // <input type='hidden' name='max_file_size' value='$max_size' />
        return "
			<div class='mini_upload'>
			".make_form(make_link("upload"), "POST", $multipart=true)."
				<input id='data[]' name='data[]' size='16' type='file' accept='$accept' multiple>
				<input name='tags' type='text' placeholder='tagme' class='autocomplete_tags' required='required' autocomplete='off'>
				<input type='submit' value='Post'>
			</form>
			<small>(Max file size is $max_kb)</small>
			<noscript><br><a href='".make_link("upload")."'>Larger Form</a></noscript>
			</div>
		";
    }

    protected function get_accept(): string
    {
        return ".".join(",.", DataHandlerExtension::get_all_supported_exts());
    }
}
