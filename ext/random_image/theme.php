<?php declare(strict_types=1);
use function MicroHTML\{DIV,A,IMG};

class RandomImageTheme extends Themelet
{
    public function display_random(Page $page, Image $image)
    {
        $page->add_block(new Block("Random Image", $this->build_random_html($image), "left", 8));
    }

    public function build_random_html(Image $image, ?string $query = null): string
    {
        $tsize = get_thumbnail_size($image->width, $image->height);

        return (string)DIV(
            ["style"=>"text-align: center;"],
            A(
                [
                    "href"=>make_link("post/view/{$image->id}", $query),
                    "style"=>"position: relative; height: {$tsize[1]}px; width: {$tsize[0]}px;"
                ],
                IMG([
                    "id"=>"thumb_rand_{$image->id}",
                    "title"=>$image->get_tooltip(),
                    "alt"=>$image->get_tooltip(),
                    "class"=>'highlighted',
                    "style"=>"height: {$tsize[1]}px; width: {$tsize[0]}px;",
                    "src"=>$image->get_thumb_link()
                ])
            )
        );
    }
}
