<?php declare(strict_types=1);
use function MicroHTML\INPUT;

class FeaturedTheme extends Themelet
{
    public function display_featured(Page $page, Image $image): void
    {
        $page->add_block(new Block("Featured Image", $this->build_featured_html($image), "left", 3));
    }

    public function get_buttons_html(int $image_id): string
    {
        return (string)SHM_SIMPLE_FORM(
            make_link("featured_image/set"),
            INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image_id]),
            INPUT(["type"=>'submit', "value"=>'Feature This']),
        );
    }

    public function build_featured_html(Image $image, ?string $query=null): string
    {
        $i_id = $image->id;
        $h_view_link = make_link("post/view/$i_id", $query);
        $h_thumb_link = $image->get_thumb_link();
        $h_tip = html_escape($image->get_tooltip());
        $tsize = get_thumbnail_size($image->width, $image->height);

        return "
			<a href='$h_view_link'>
				<img id='thumb_{$i_id}' title='{$h_tip}' alt='{$h_tip}' class='highlighted' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='{$h_thumb_link}'>
			</a>
		";
    }
}
