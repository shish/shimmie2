<?php

declare(strict_types=1);

namespace Shimmie2;

final class LinkImage extends Extension
{
    public const KEY = "link_image";
    /** @var LinkImageTheme */
    protected Themelet $theme;

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page;
        $this->theme->links_block($page, $this->data($event->image));
    }

    /**
     * @return array{thumb_src: string, image_src: string, post_link: string, text_link: string|null}
     */
    private function data(Image $image): array
    {
        global $config;

        $text_link = $image->parse_link_template($config->get_string(LinkImageConfig::TEXT_FORMAT));
        $text_link = trim($text_link) == "" ? null : $text_link; // null blank setting so the url gets filled in on the text links.

        return [
            'thumb_src' => (string)$image->get_thumb_link()->asAbsolute(),
            'image_src' => (string)$image->get_image_link()->asAbsolute(),
            'post_link' => (string)make_link("post/view/{$image->id}")->asAbsolute(),
            'text_link' => $text_link
        ];
    }
}
