<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArchiveFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_archive";
    public const SUPPORTED_MIME = [MimeType::ZIP];

    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->supported_mime($event->mime)) {
            $tmpdir = shm_tempdir("archive");
            $cmd = Ctx::$config->req_string(ArchiveFileHandlerConfig::EXTRACT_COMMAND);
            $cmd = str_replace('"%f"', "%f", $cmd);
            $cmd = str_replace('"%d"', "%d", $cmd);
            $cmd = str_replace('%f', escapeshellarg($event->tmpname->str()), $cmd);
            $cmd = str_replace('%d', escapeshellarg($tmpdir->str()), $cmd);
            exec($cmd);
            if ($tmpdir->exists()) {
                try {
                    $results = send_event(new DirectoryUploadEvent($tmpdir, Tag::explode($event->metadata['tags'])))->results;
                    foreach ($results as $r) {
                        if (is_a($r, UploadError::class)) {
                            Ctx::$page->flash($r->name." failed: ".$r->error);
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

    protected function check_contents(Path $tmpname): bool
    {
        return false;
    }

    protected function create_thumb(Image $image): bool
    {
        return false;
    }
}
