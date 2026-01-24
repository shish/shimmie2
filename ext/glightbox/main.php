<?php

namespace Shimmie2;

use function MicroHTML\{LINK, SCRIPT};

final class GLightboxConfig extends ConfigGroup
{
    public const string KEY = GLightboxInfo::KEY;

    #[ConfigMeta("Show post title", ConfigType::BOOL, default: true)]
    public const string SHOW_TITLE = "glightbox_show_title";

    #[ConfigMeta("Show post description", ConfigType::BOOL, default: true)]
    public const string SHOW_DESCRIPTION = "glightbox_show_description";
}

/** @extends DataHandlerExtension<GLightboxTheme> */
final class GLightbox extends DataHandlerExtension
{
    public const string KEY = GLightboxInfo::KEY;
    public const array SUPPORTED_MIME = ImageFileHandler::SUPPORTED_MIME;

    public function get_priority(): int
    {
        return 49; // Before handle_image
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if ($this->supported_mime($event->image->get_mime())) {
            $this->add_html_headers();
            parent::onDisplayingImage($event);
        }
    }

    private function add_html_headers(): void
    {
        $data_href = Url::base();

        Ctx::$page->add_html_header(SCRIPT(["src" => "{$data_href}/ext/glightbox/glightbox.min.js", "defer" => true]), 10);
        Ctx::$page->add_html_header(LINK([
            "rel" => "stylesheet",
            "type" => "text/css",
            "href" => "{$data_href}/ext/glightbox/glightbox.min.css"
        ]), 10);
    }

    protected function supported_mime(MimeType $mime): bool
    {
        return MimeType::matches_array($mime, $this::SUPPORTED_MIME);
    }

    protected function media_check_properties(Image $image): ?MediaProperties
    {
        return null;
    }

    protected function check_contents(Path $tmpname): bool
    {
        return false;
    }

    protected function create_thumb(Image $image): bool
    {
        return false;
    }
}
