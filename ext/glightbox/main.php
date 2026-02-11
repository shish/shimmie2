<?php

namespace Shimmie2;

use function MicroHTML\{LINK, SCRIPT};

final class GLightboxConfig extends ConfigGroup
{
    public const string KEY = GLightboxViewerInfo::KEY;

    #[ConfigMeta("Show post title", ConfigType::BOOL, default: true)]
    public const string SHOW_TITLE = "glightbox_show_title";

    #[ConfigMeta("Show post description", ConfigType::BOOL, default: true)]
    public const string SHOW_DESCRIPTION = "glightbox_show_description";
}

/** @extends DataHandlerExtension<GLightboxViewerTheme> */
final class GLightboxViewer extends DataHandlerExtension
{
    public const string KEY = GLightboxViewerInfo::KEY;
    public const array SUPPORTED_MIME = ImageFileHandler::SUPPORTED_MIME;

    #[EventListener]
    public function onDataUpload(DataUploadEvent $event): void
    {
    }

    #[EventListener]
    public function onThumbnailGeneration(ThumbnailGenerationEvent $event): void
    {
    }

    #[EventListener]
    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event): void
    {
    }

    #[EventListener]
    public function onBuildSupportedMimes(BuildSupportedMimesEvent $event): void
    {
    }

    #[EventListener]
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
