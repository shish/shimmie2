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
 *
 * @template TThemelet of Themelet = Themelet
 * @extends Extension<TThemelet>
 */
abstract class DataHandlerExtension extends Extension
{
    /** @var string[] */
    protected const SUPPORTED_MIME = [];

    #[EventListener]
    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->supported_mime($event->mime)) {
            if (!$this->check_contents($event->tmpname)) {
                // We DO support this extension - but the file looks corrupt
                throw new UploadException("Invalid or corrupted file");
            }

            $existing = Image::by_hash($event->tmpname->md5());
            if (!is_null($existing)) {
                if (Ctx::$config->get(UploadConfig::COLLISION_HANDLER) === 'merge') {
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
            assert($filename->is_readable());
            $image = new Image();
            $image->tmp_file = $filename;
            $filesize = $filename->filesize();
            if ($filesize === 0) {
                throw new UploadException("File size is zero");
            }
            $image->filesize = $filesize;
            $image->hash = $filename->md5();
            // DB limits to 255 char filenames
            $image->filename = truncate_filename($event->filename);
            $image->set_mime($event->mime);
            try {
                send_event(new MediaCheckPropertiesEvent($image));
            } catch (MediaException $e) {
                throw new UploadException("Unable to scan media properties {$filename->str()} / {$image->filename} / $image->hash: ".$e->getMessage());
            }
            $image->save_to_db(); // Ensure the image has a DB-assigned ID

            $iae = send_event(new ImageAdditionEvent($image));
            send_event(new ImageInfoSetEvent($image, $event->slot, $event->metadata));

            // If everything is OK, then move the file to the archive
            $filename = Filesystem::warehouse_path(Image::IMAGE_DIR, $event->hash);
            try {
                $event->tmpname->copy($filename);
            } catch (\Exception $e) {
                throw new UploadException("Failed to copy file from uploads ({$event->tmpname->str()}) to archive ({$filename->str()}): ".$e->getMessage());
            }

            $event->images[] = $iae->image;
        }
    }

    #[EventListener]
    public function onThumbnailGeneration(ThumbnailGenerationEvent $event): void
    {
        $result = false;
        if ($this->supported_mime($event->image->get_mime())) {
            if ($event->force) {
                $result = $this->create_thumb($event->image);
            } else {
                $outname = $event->image->get_thumb_filename();
                if ($outname->exists()) {
                    return;
                }
                $result = $this->create_thumb($event->image);
            }
        }
        if ($result) {
            $event->generated = true;
        }
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if ($this->supported_mime($event->image->get_mime())) {
            if (!$event->displayed) {
                $this->display_media($event->image);
                $event->displayed = true;
            }
            if (Ctx::$config->get(ImageConfig::SHOW_META) && method_exists($this->theme, "display_metadata")) {
                $this->theme->display_metadata($event->image);
            }
        }
    }

    #[EventListener]
    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event): void
    {
        if ($this->supported_mime($event->image->get_mime())) {
            $properties = $this->media_check_properties($event->image);
            if (is_null($properties)) {
                // No properties were found, so we can't do anything
                Log::debug(static::KEY, "No properties found for image {$event->image->hash}");
                return;
            }
            $event->image->width = $properties->width ?? 0;
            $event->image->height = $properties->height ?? 0;
            $event->image->lossless = $properties->lossless;
            $event->image->image = $properties->image;
            $event->image->audio = $properties->audio;
            $event->image->video = $properties->video;
            $event->image->video_codec = $properties->video_codec;
            $event->image->length = $properties->length;
            Log::debug(static::KEY, "Scanned properties for {$event->image->hash}: " . json_encode($properties));
        }
    }

    abstract protected function media_check_properties(Image $image): ?MediaProperties;
    abstract protected function check_contents(Path $tmpname): bool;
    abstract protected function create_thumb(Image $image): bool;

    protected function supported_mime(MimeType $mime): bool
    {
        return MimeType::matches_array($mime, $this::SUPPORTED_MIME);
    }

    #[EventListener]
    public function onBuildSupportedMimes(BuildSupportedMimesEvent $event): void
    {
        $event->add_mimes(array_map(fn ($mime) => new MimeType($mime), $this::SUPPORTED_MIME));
    }

    /**
     * @return MimeType[]
     */
    public static function get_all_supported_mimes(): array
    {
        return send_event(new BuildSupportedMimesEvent())->get_mimes();
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

    protected function display_media(Image $image): void
    {
        // @phpstan-ignore-next-line
        $media = $this->theme->build_media($image);
        Ctx::$page->add_block(new Block(null, $media, "main", 10, id: static::KEY . "_media"));
    }
}
