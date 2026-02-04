<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<LinkImageTheme> */
final class LinkImage extends Extension
{
    public const KEY = "link_image";

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $this->theme->links_block($this->data($event->image));
    }

    /**
     * @return array{thumb_src: Url, image_src: Url, post_link: Url, text_link: string|null}
     */
    private function data(Image $image): array
    {
        $text_link = $image->parse_link_template(Ctx::$config->get(LinkImageConfig::TEXT_FORMAT));
        $text_link = trim($text_link) === "" ? null : $text_link; // null blank setting so the url gets filled in on the text links.

        return [
            'thumb_src' => $image->get_thumb_link()->asAbsolute(),
            'image_src' => $image->get_image_link()->asAbsolute(),
            'post_link' => make_link("post/view/{$image->id}")->asAbsolute(),
            'text_link' => $text_link
        ];
    }
}
