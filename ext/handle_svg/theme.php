<?php

declare(strict_types=1);

class SVGFileHandlerTheme extends Themelet
{
    public function display_image(Page $page, Image $image)
    {
        $ilink = make_link("get_svg/{$image->id}/{$image->id}.svg");
        //		$ilink = $image->get_image_link();
        $html = "
			<img
			    alt='main image'
			    src='$ilink'
			    id='main_image'
			    class='shm-main-image'
			    data-width='{$image->width}'
			    data-height='{$image->height}'
			    />
		";
        $page->add_block(new Block("Image", $html, "main", 10));
    }
}
