<?php

declare(strict_types=1);

namespace Shimmie2;

class LinkImage extends Extension
{
    /** @var LinkImageTheme */
    protected Themelet $theme;

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page;
        $this->theme->links_block($page, $this->data($event->image));
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Link to Post");
        $sb->add_text_option("ext_link-img_text-link_format", "Text Link Format: ");
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string("ext_link-img_text-link_format", '$title - $id ($ext $size $filesize)');
    }

    /**
     * @return array{thumb_src: string, image_src: string, post_link: string, text_link: string|null}
     */
    private function data(Image $image): array
    {
        global $config;

        $text_link = $image->parse_link_template($config->get_string("ext_link-img_text-link_format"));
        $text_link = trim($text_link) == "" ? null : $text_link; // null blank setting so the url gets filled in on the text links.

        return [
            'thumb_src' => make_http($image->get_thumb_link()),
            'image_src' => make_http($image->get_image_link()),
            'post_link' => make_http(make_link("post/view/{$image->id}")),
            'text_link' => $text_link
        ];
    }
}
