<?php

declare(strict_types=1);

namespace Shimmie2;

class ArchiveFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_archive";
    protected array $SUPPORTED_MIME = [MimeType::ZIP];

    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->supported_mime($event->mime)) {
            global $config, $page;
            $tmpdir = shm_tempnam("archive");
            unlink($tmpdir);
            mkdir($tmpdir, 0755, true);
            $cmd = $config->get_string(ArchiveFileHandlerConfig::EXTRACT_COMMAND);
            $cmd = str_replace('%f', $event->tmpname, $cmd);
            $cmd = str_replace('%d', $tmpdir, $cmd);
            assert(is_string($cmd));
            exec($cmd);
            if (file_exists($tmpdir)) {
                try {
                    $results = send_event(new DirectoryUploadEvent($tmpdir, Tag::explode($event->metadata['tags'])))->results;
                    foreach ($results as $r) {
                        if (is_a($r, UploadError::class)) {
                            $page->flash($r->name." failed: ".$r->error);
                        }
                        if (is_a($r, UploadSuccess::class)) {
                            $event->images[] = Image::by_id_ex($r->image_id);
                        }
                    }
                } finally {
                    Filesystem::deltree($tmpdir);
                }
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
    }

    // we don't actually do anything, just accept one upload and spawn several
    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
    }

    protected function check_contents(string $tmpname): bool
    {
        return false;
    }

    protected function create_thumb(Image $image): bool
    {
        return false;
    }
}
