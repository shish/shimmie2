<?php

class MediaTheme extends Themelet
{
    public function display_form(array $types)
    {
        global $page, $database;

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
}
