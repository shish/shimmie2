<?php declare(strict_types=1);
use function MicroHTML\INPUT;

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
        return (string)SHM_SIMPLE_FORM(
            "image_hash_ban/add",
            INPUT(["type"=>'hidden', "name"=>'c_hash', "value"=>$image->hash]),
            INPUT(["type"=>'hidden', "name"=>'c_image_id', "value"=>$image->id]),
            INPUT(["type"=>'text', "name"=>'c_reason']),
            INPUT(["type"=>'submit', "value"=>'Ban Hash and Delete Image']),
        );
    }
}
