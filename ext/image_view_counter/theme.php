<?php declare(strict_types=1);

class ImageViewCounterTheme extends Themelet
{
    public function view_popular($images)
    {
        global $page, $config;
        $pop_images = "";
        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);
           $pop_images .= $thumb_html . "\n";
        }


        $html = "\n".
            "<h3 style='text-align: center;'>\n".
            "	<a href='{$b_dte}'>&laquo;</a> {$dte[1]} <a href='{$f_dte}'>&raquo;</a>\n".
            "</h3>\n".
            "<br/>\n".$pop_images;


        $nav_html = "<a href=".make_link().">Index</a>";

        $page->set_heading($config->get_string(SetupConfig::TITLE));
        $page->add_block(new Block("Navigation", $nav_html, "left", 10));
        $page->add_block(new Block(null, $html, "main", 30));
    }


    public function get_help_html()
    {
        return '<p>Search for images that have received views by users.</p>';
       
    }
}
