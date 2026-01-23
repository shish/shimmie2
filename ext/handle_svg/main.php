<?php

declare(strict_types=1);

namespace Shimmie2;

use enshrined\svgSanitize\Sanitizer;

/** @extends DataHandlerExtension<SVGFileHandlerTheme> */
final class SVGFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_svg";
    public const SUPPORTED_MIME = [MimeType::SVG];

    #[EventListener]
    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->supported_mime($event->mime)) {
            // If the SVG handler intends to handle this file,
            // then sanitise it before touching it
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = $event->tmpname->get_contents();
            $cleanSVG = false_throws($sanitizer->sanitize($dirtySVG));
            $event->hash = md5($cleanSVG);
            $new_tmpname = shm_tempnam("svg");
            $new_tmpname->put_contents($cleanSVG);
            $event->set_tmpname($new_tmpname);
            parent::onDataUpload($event);
            $new_tmpname->unlink();
        }
    }

    protected function media_check_properties(Image $image): MediaProperties
    {
        $msp = new MiniSVGParser($image->get_image_filename()->str());
        return new MediaProperties(
            width: $msp->width,
            height: $msp->height,
            lossless: true,
            video: false,
            audio: false,
            image: true,
            video_codec: null,
            length: null,
        );
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
            (new Path("ext/handle_svg/thumb.jpg"))->copy($image->get_thumb_filename());
            return false;
        }
    }

    protected function check_contents(Path $tmpname): bool
    {
        if (MimeType::get_for_file($tmpname)->base !== MimeType::SVG) {
            return false;
        }

        $msp = new MiniSVGParser($tmpname->str());
        return $msp->valid;
    }
}

final class MiniSVGParser
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
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function startElement(mixed $parser, string $name, array $attrs): void
    {
        if ($name === "SVG" && $this->xml_depth === 0) {
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
