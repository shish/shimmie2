<?php

declare(strict_types=1);

namespace Shimmie2;

use enshrined\svgSanitize\Sanitizer;

class SVGFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_svg";
    public const SUPPORTED_MIME = [MimeType::SVG];

    /** @var SVGFileHandlerTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;
        if ($event->page_matches("get_svg/{id}")) {
            $id = $event->get_iarg('id');
            $image = Image::by_id_ex($id);
            $hash = $image->hash;

            $page->set_mime(MimeType::SVG);
            $page->set_mode(PageMode::DATA);

            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = \Safe\file_get_contents(Filesystem::warehouse_path(Image::IMAGE_DIR, $hash));
            $cleanSVG = $sanitizer->sanitize($dirtySVG);
            $page->set_data($cleanSVG);
        }
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config;

        if ($this->supported_mime($event->mime)) {
            // If the SVG handler intends to handle this file,
            // then sanitise it before touching it
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = \Safe\file_get_contents($event->tmpname);
            $cleanSVG = false_throws($sanitizer->sanitize($dirtySVG));
            $event->hash = md5($cleanSVG);
            $new_tmpname = shm_tempnam("svg");
            file_put_contents($new_tmpname, $cleanSVG);
            $event->set_tmpname($new_tmpname);
            parent::onDataUpload($event);
            unlink($new_tmpname);
        }
    }

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->lossless = true;
        $event->image->video = false;
        $event->image->audio = false;
        $event->image->image = true;

        $msp = new MiniSVGParser($event->image->get_image_filename());
        $event->image->width = $msp->width;
        $event->image->height = $msp->height;
    }

    protected function create_thumb(Image $image): bool
    {
        try {
            // Normally we require imagemagick, but for unit tests we can use a no-op engine
            if (defined('UNITTEST')) {
                ThumbnailUtil::create_image_thumb($image);
            } else {
                ThumbnailUtil::create_image_thumb($image, MediaEngine::IMAGICK);
            }
            return true;
        } catch (MediaException $e) {
            Log::warning("handle_svg", "Could not generate thumbnail. " . $e->getMessage());
            copy("ext/handle_svg/thumb.jpg", $image->get_thumb_filename());
            return false;
        }
    }

    protected function check_contents(string $tmpname): bool
    {
        if (MimeType::get_for_file($tmpname) !== MimeType::SVG) {
            return false;
        }

        $msp = new MiniSVGParser($tmpname);
        return $msp->valid;
    }
}

class MiniSVGParser
{
    public bool $valid = false;
    /** @var positive-int */
    public int $width;
    /** @var positive-int */
    public int $height;
    private int $xml_depth = 0;

    public function __construct(string $file)
    {
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, [$this, "startElement"], [$this, "endElement"]);
        $this->valid = xml_parse($xml_parser, \Safe\file_get_contents($file), true) === 1;
        xml_parser_free($xml_parser);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function startElement(mixed $parser, string $name, array $attrs): void
    {
        if ($name == "SVG" && $this->xml_depth == 0) {
            $w = int_escape($attrs["WIDTH"]);
            $h = int_escape($attrs["HEIGHT"]);
            assert($w > 0);
            assert($h > 0);
            $this->width = $w;
            $this->height = $h;
        }
        $this->xml_depth++;
    }

    public function endElement(mixed $parser, string $name): void
    {
        $this->xml_depth--;
    }
}
