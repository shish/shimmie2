<?php

declare(strict_types=1);

namespace Shimmie2;

use enshrined\svgSanitize\Sanitizer;

class SVGFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::SVG];

    /** @var SVGFileHandlerTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;
        if ($event->page_matches("get_svg")) {
            $id = int_escape($event->get_arg(0));
            $image = Image::by_id($id);
            $hash = $image->hash;

            $page->set_mime(MimeType::SVG);
            $page->set_mode(PageMode::DATA);

            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = file_get_contents(warehouse_path(Image::IMAGE_DIR, $hash));
            $cleanSVG = $sanitizer->sanitize($dirtySVG);
            $page->set_data($cleanSVG);
        }
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        global $config;

        if ($this->supported_mime($event->mime)) {
            // If the SVG handler intends to handle this file,
            // then sanitise it before touching it
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = file_get_contents($event->tmpname);
            $cleanSVG = $sanitizer->sanitize($dirtySVG);
            $event->hash = md5($cleanSVG);
            $new_tmpname = tempnam(sys_get_temp_dir(), "shimmie_svg");
            file_put_contents($new_tmpname, $cleanSVG);
            $event->set_tmpname($new_tmpname);

            parent::onDataUpload($event);
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
                create_image_thumb($image);
            } else {
                create_image_thumb($image, MediaEngine::IMAGICK);
            }
            return true;
        } catch (MediaException $e) {
            log_warning("handle_svg", "Could not generate thumbnail. " . $e->getMessage());
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
        return bool_escape($msp->valid);
    }
}

class MiniSVGParser
{
    public bool $valid = false;
    public int $width = 0;
    public int $height = 0;
    private int $xml_depth = 0;

    public function __construct(string $file)
    {
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, [$this, "startElement"], [$this, "endElement"]);
        $this->valid = bool_escape(xml_parse($xml_parser, file_get_contents($file), true));
        xml_parser_free($xml_parser);
    }

    public function startElement($parser, $name, $attrs): void
    {
        if ($name == "SVG" && $this->xml_depth == 0) {
            $this->width = int_escape($attrs["WIDTH"]);
            $this->height = int_escape($attrs["HEIGHT"]);
        }
        $this->xml_depth++;
    }

    public function endElement($parser, $name): void
    {
        $this->xml_depth--;
    }
}
