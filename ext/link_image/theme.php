<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, FIELDSET, INPUT, LABEL, LEGEND, TABLE, TD, TR};

use MicroHTML\HTMLElement;

class LinkImageTheme extends Themelet
{
    /**
     * @param array{thumb_src:Url,image_src:Url,post_link:Url,text_link:string|null} $data
     */
    public function links_block(array $data): void
    {
        $thumb_src = $data['thumb_src'];
        $image_src = $data['image_src'];
        $post_link = $data['post_link'];
        $text_link = $data['text_link'];

        $html = TABLE(TR(
            TD(FIELDSET(
                LEGEND(A(["href" => "https://en.wikipedia.org/wiki/Bbcode", "target" => "_blank"], "BBCode")),
                TABLE(
                    $this->link_code("Link", $this->url($post_link, $text_link, "ubb"), "ubb_text-link"),
                    $this->link_code("Thumb", $this->url($post_link, $this->img($thumb_src, "ubb"), "ubb"), "ubb_thumb-link"),
                    $this->link_code("File", $this->img($image_src, "ubb"), "ubb_full-img"),
                )
            )),
            TD(FIELDSET(
                LEGEND(A(["href" => "https://en.wikipedia.org/wiki/Html", "target" => "_blank"], "HTML")),
                TABLE(
                    $this->link_code("Link", $this->url($post_link, $text_link, "html"), "html_text-link"),
                    $this->link_code("Thumb", $this->url($post_link, $this->img($thumb_src, "html"), "html"), "html_thumb-link"),
                    $this->link_code("File", $this->img($image_src, "html"), "html_full-img"),
                )
            )),
            TD(FIELDSET(
                LEGEND(A(["href" => "https://en.wikipedia.org/wiki/Markdown", "target" => "_blank"], "Markdown")),
                TABLE(
                    $this->link_code("Link", $this->url($post_link, $text_link, "markdown"), "markdown_text-link"),
                    $this->link_code("Thumb", $this->url($post_link, $this->img($thumb_src, "markdown"), "markdown"), "markdown_thumb-link"),
                    $this->link_code("File", $this->img($image_src, "markdown"), "markdown_full-img"),
                )
            )),
            TD(FIELDSET(
                LEGEND("Plain Text"),
                TABLE(
                    $this->link_code("Link", (string)$post_link, "text_post-link"),
                    $this->link_code("Thumb", (string)$thumb_src, "text_thumb-url"),
                    $this->link_code("File", (string)$image_src, "text_image-src"),
                )
            )),
        ));
        Ctx::$page->add_block(new Block("Link to Post", $html, "main", 50));
    }

    protected function url(Url $url, ?string $content, string $type): string
    {
        if (empty($content)) {
            $content = $url;
        }

        return match($type) {
            "html" => "<a href=\"".$url."\">".$content."</a>",
            "ubb" => "[url=".$url."]".$content."[/url]",
            "markdown" => "[[".$url."|".$content."]]",
            default => $url." - ".$content,
        };
    }

    protected function img(Url $src, string $type): string
    {
        return match($type) {
            "html" => "<img src=\"$src\" alt=\"\" />",
            "ubb" => "[img]".$src."[/img]",
            "markdown" => "![image](".$src.")",
            default => (string)$src,
        };
    }

    protected function link_code(string $label, string $content, string $id): HTMLElement
    {
        return	TR(
            TD(
                LABEL(
                    ["for" => $id, "title" => "Click to select the textbox"],
                    $label,
                )
            ),
            TD(
                INPUT([
                    "type" => "text",
                    "readonly" => true,
                    "id" => $id,
                    "name" => $id,
                    "value" => $content,
                    "onfocus" => "this.select();"
                ])
            )
        );
    }
}
