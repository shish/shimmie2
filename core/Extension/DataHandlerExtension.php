<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A common base class for data handler extensions
 *
 * $SUPPORTED_MIME
 *   An array of MIME types that this extension can handle, eg
 *   ["image/jpeg", "image/png"]
 *
 * media_check_properties()
 *   ...?
 *
 * check_contents()
 *   should check the contents of the given file and confirm
 *   that it is a valid file of the supported type
 *
 * create_thumb()
 *   ...?
 */
abstract class DataHandlerExtension extends Extension
{
    /** @var string[] */
    protected const SUPPORTED_MIME = [];

    public function onDataUpload(DataUploadEvent $event): void
    {
        global $config;

        if ($this->supported_mime($event->mime)) {
            if (!$this->check_contents($event->tmpname)) {
                // We DO support this extension - but the file looks corrupt
                throw new UploadException("Invalid or corrupted file");
            }

            $existing = Image::by_hash(\Safe\md5_file($event->tmpname));
            if (!is_null($existing)) {
                if ($config->get_string(UploadConfig::COLLISION_HANDLER) === 'merge') {
                    // Right now tags are the only thing that get merged, so
                    // we can just send a TagSetEvent - in the future we might
                    // want a dedicated MergeEvent?
                    if (!empty($event->metadata['tags'])) {
                        $tags = Tag::explode($existing->get_tag_list() . " " . $event->metadata['tags']);
                        send_event(new TagSetEvent($existing, $tags));
                    }
                    $event->images[] = $existing;
                    return;
                } else {
                    throw new UploadException(">>{$existing->id} already has hash {$existing->hash}");
                }
            }

            // Create a new Image object
            $filename = $event->tmpname;
            assert(is_readable($filename));
            $image = new Image();
            $image->tmp_file = $filename;
            $filesize = \Safe\filesize($filename);
            if ($filesize === 0) {
                throw new UploadException("File size is zero");
            }
            $image->filesize = $filesize;
            $image->hash = \Safe\md5_file($filename);
            // DB limits to 255 char filenames
            $image->filename = substr($event->filename, -250);
            $image->set_mime($event->mime);
            try {
                send_event(new MediaCheckPropertiesEvent($image));
            } catch (MediaException $e) {
                throw new UploadException("Unable to scan media properties $filename / $image->filename / $image->hash: ".$e->getMessage());
            }
            $image->save_to_db(); // Ensure the image has a DB-assigned ID

            $iae = send_event(new ImageAdditionEvent($image));
            send_event(new ImageInfoSetEvent($image, $event->slot, $event->metadata));

            // If everything is OK, then move the file to the archive
            $filename = Filesystem::warehouse_path(Image::IMAGE_DIR, $event->hash);
            try {
                \Safe\copy($event->tmpname, $filename);
            } catch (\Exception $e) {
                throw new UploadException("Failed to copy file from uploads ({$event->tmpname}) to archive ($filename): ".$e->getMessage());
            }

            $event->images[] = $iae->image;
        }
    }

    public function onThumbnailGeneration(ThumbnailGenerationEvent $event): void
    {
        $result = false;
        if ($this->supported_mime($event->image->get_mime())) {
            if ($event->force) {
                $result = $this->create_thumb($event->image);
            } else {
                $outname = $event->image->get_thumb_filename();
                if (file_exists($outname)) {
                    return;
                }
                $result = $this->create_thumb($event->image);
            }
        }
        if ($result) {
            $event->generated = true;
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $config, $page;
        if ($this->supported_mime($event->image->get_mime())) {
            // @phpstan-ignore-next-line
            $this->theme->display_image($event->image);
            if ($config->get_bool(ImageConfig::SHOW_META) && method_exists($this->theme, "display_metadata")) {
                $this->theme->display_metadata($event->image);
            }
        }
    }

    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event): void
    {
        if ($this->supported_mime($event->image->get_mime())) {
            $this->media_check_properties($event);
        }
    }

    abstract protected function media_check_properties(MediaCheckPropertiesEvent $event): void;
    abstract protected function check_contents(string $tmpname): bool;
    abstract protected function create_thumb(Image $image): bool;

    protected function supported_mime(string $mime): bool
    {
        return MimeType::matches_array($mime, $this::SUPPORTED_MIME);
    }

    /**
     * @return string[]
     */
    public static function get_all_supported_mimes(): array
    {
        $arr = [];
        foreach (DataHandlerExtension::get_subclasses() as $class) {
            $handler = $class->newInstance();
            $arr = array_merge($arr, $handler::SUPPORTED_MIME);
        }

        // Not sure how to handle this otherwise, don't want to set up a whole other event for this one class
        if (TranscodeImageInfo::is_enabled()) {
            $arr = array_merge($arr, TranscodeImage::get_enabled_mimes());
        }

        $arr = array_unique($arr);
        return $arr;
    }

    /**
     * @return string[]
     */
    public static function get_all_supported_exts(): array
    {
        $arr = [];
        foreach (self::get_all_supported_mimes() as $mime) {
            $arr = array_merge($arr, FileExtension::get_all_for_mime($mime));
        }
        $arr = array_unique($arr);
        return $arr;
    }
}
