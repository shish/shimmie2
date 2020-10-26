<?php declare(strict_types=1);
use function MicroHTML\INPUT;
use function MicroHTML\DIV;
use function MicroHTML\A;
use function MicroHTML\IMG;

class FeaturedTheme extends Themelet
{
    public function display_featured(Page $page, Image $image): void
    {
        $page->add_block(new Block("Featured Post", $this->build_featured_html($image), "left", 3));
    }

    public function get_buttons_html(int $image_id): string
    {
        return (string)SHM_SIMPLE_FORM(
            "featured_image/set",
            INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image_id]),
            INPUT(["type"=>'submit', "value"=>'Feature This']),
        );
    }

    public function build_featured_html(Image $image, ?string $query=null): string
    {
        $tsize = get_thumbnail_size($image->width, $image->height);

        return (string)DIV(
            ["style"=>"text-align: center;"],
            A(
                ["href"=>make_link("post/view/{$image->id}", $query)],
                IMG([
                    "id"=>"thumb_rand_{$image->id}",
                    "title"=>$image->get_tooltip(),
                    "alt"=>$image->get_tooltip(),
                    "class"=>'highlighted',
                    "style"=>"max-height: {$tsize[1]}px; max-width: 100%;",
                    "src"=>$image->get_thumb_link()
                ])
            )
        );
    }
}
