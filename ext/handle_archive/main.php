<?php

declare(strict_types=1);

namespace Shimmie2;

class ArchiveFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::ZIP];

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(ArchiveFileHandlerConfig::EXTRACT_COMMAND, 'unzip -d "%d" "%f"');
    }

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
                    $results = add_dir($tmpdir, Tag::explode($event->metadata['tags']));
                    foreach ($results as $r) {
                        if (is_a($r, UploadError::class)) {
                            $page->flash($r->name." failed: ".$r->error);
                        }
                        if (is_a($r, UploadSuccess::class)) {
                            $event->images[] = Image::by_id_ex($r->image_id);
                        }
                    }
                } finally {
                    deltree($tmpdir);
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
