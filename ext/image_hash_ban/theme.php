<?php

class ImageBanTheme extends Themelet
{
    /*
     * Show all the bans
     */
    public function display_bans(Page $page, $table, $paginator)
    {
        $page->set_title("Image Bans");
        $page->set_heading("Image Bans");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Edit Image Bans", $table . $paginator));
    }

    /*
     * Display a link to delete an image
     */
    public function get_buttons_html(Image $image)
    {
        $html = "
			".make_form(make_link("image_hash_ban/add"))."
				<input type='hidden' name='c_hash' value='{$image->hash}'>
				<input type='hidden' name='c_image_id' value='{$image->id}'>
				<input type='text' name='c_reason'>
				<input type='submit' value='Ban Hash and Delete Image'>
			</form>
		";
        return $html;
    }
}
