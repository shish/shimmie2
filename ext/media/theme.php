<?php declare(strict_types=1);

class MediaTheme extends Themelet
{
    public function display_form(array $types)
    {
        global $page;

        $html = "Use this to force scanning for media properties.";
        $html .= make_form(make_link("admin/media_rescan"));
        $html .= "<table class='form'>";
        $html .= "<tr><th>Image Type</th><td><select name='media_rescan_type'><option value=''>All</option>";
        foreach ($types as $type) {
            $html .= "<option value='".$type["ext"]."'>".$type["ext"]." (".$type["count"].")</option>";
        }
        $html .= "</select></td></tr>";
        $html .= "<tr><td colspan='2'><input type='submit' value='Scan Media Information'></td></tr>";
        $html .= "</table></form>\n";
        $page->add_block(new Block("Media Tools", $html));
    }

    public function get_buttons_html(int $image_id): string
    {
        return "
			".make_form(make_link("media_rescan/"))."
			<input type='hidden' name='image_id' value='$image_id'>
			<input type='submit' value='Scan Media Properties'>
			</form>
		";
    }

    public function get_help_html()
    {
        return '<p>Search for items based on the type of media.</p>
        <div class="command_example">
        <pre>content:audio</pre>
        <p>Returns items that contain audio, including videos and audio files.</p>
        </div> 
        <div class="command_example">
        <pre>content:video</pre>
        <p>Returns items that contain video, including animated GIFs.</p>
        </div>
        <p>These search terms depend on the items being scanned for media content. Automatic scanning was implemented in mid-2019, so items uploaded before, or items uploaded on a system without ffmpeg, will require additional scanning before this will work.</p> 
        ';
    }
}
