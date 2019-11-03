<?php
use enshrined\svgSanitize\Sanitizer;

class SVGFileHandler extends DataHandlerExtension
{
    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event)
    {
        switch ($event->ext) {
            case "svg":
                $event->lossless = true;
                $event->video = false;
                $event->audio = false;
                $event->image = true;

                $msp = new MiniSVGParser($event->file_name);
                $event->width = $msp->width;
                $event->height = $msp->height;

                break;
        }
    }


    public function onDataUpload(DataUploadEvent $event)
    {
        if ($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
            $hash = $event->hash;

            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = file_get_contents($event->tmpname);
            $cleanSVG = $sanitizer->sanitize($dirtySVG);
            file_put_contents(warehouse_path(Image::IMAGE_DIR, $hash), $cleanSVG);

            send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
            $image = $this->create_image_from_data(warehouse_path(Image::IMAGE_DIR, $hash), $event->metadata);
            if (is_null($image)) {
                throw new UploadException("SVG handler failed to create image object from data");
            }
            $iae = new ImageAdditionEvent($image);
            send_event($iae);
            $event->image_id = $iae->image->id;
            $event->merged = $iae->merged;
        }
    }

    protected function create_thumb(string $hash, string $type): bool
    {
        try {
            create_image_thumb($hash, $type, MediaEngine::IMAGICK);
            return true;
        } catch (MediaException $e) {
            log_warning("handle_svg", "Could not generate thumbnail. " . $e->getMessage());
            copy("ext/handle_svg/thumb.jpg", warehouse_path(Image::THUMBNAIL_DIR, $hash));
            return false;
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page;
        if ($this->supported_ext($event->image->ext)) {
            $this->theme->display_image($page, $event->image);
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;
        if ($event->page_matches("get_svg")) {
            $id = int_escape($event->get_arg(0));
            $image = Image::by_id($id);
            $hash = $image->hash;

            $page->set_type("image/svg+xml");
            $page->set_mode(PageMode::DATA);

            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $dirtySVG = file_get_contents(warehouse_path(Image::IMAGE_DIR, $hash));
            $cleanSVG = $sanitizer->sanitize($dirtySVG);
            $page->set_data($cleanSVG);
        }
    }

    protected function supported_ext(string $ext): bool
    {
        $exts = ["svg"];
        return in_array(strtolower($ext), $exts);
    }

    protected function create_image_from_data(string $filename, array $metadata): Image
    {
        $image = new Image();

        $image->filesize  = $metadata['size'];
        $image->hash      = $metadata['hash'];
        $image->filename  = $metadata['filename'];
        $image->ext       = $metadata['extension'];
        $image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
        $image->source    = $metadata['source'];

        return $image;
    }

    protected function check_contents(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $msp = new MiniSVGParser($file);
        return bool_escape($msp->valid);
    }
}

class MiniSVGParser
{
    /** @var bool */
    public $valid=false;
    /** @var int */
    public $width=0;
    /** @var int */
    public $height=0;

    /** @var int */
    private $xml_depth=0;

    public function __construct(string $file)
    {
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, [$this, "startElement"], [$this, "endElement"]);
        $this->valid = bool_escape(xml_parse($xml_parser, file_get_contents($file), true));
        xml_parser_free($xml_parser);
    }

    public function startElement($parser, $name, $attrs)
    {
        if ($name == "SVG" && $this->xml_depth == 0) {
            $this->width = int_escape($attrs["WIDTH"]);
            $this->height = int_escape($attrs["HEIGHT"]);
        }
        $this->xml_depth++;
    }

    public function endElement($parser, $name)
    {
        $this->xml_depth--;
    }
}
