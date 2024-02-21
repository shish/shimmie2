<?php

declare(strict_types=1);

namespace Shimmie2;

class ArchiveFileHandler extends DataHandlerExtension
{
    protected array $SUPPORTED_MIME = [MimeType::ZIP];

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string('archive_extract_command', 'unzip -d "%d" "%f"');
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Archive Handler Options");
        $sb->add_text_option("archive_tmp_dir", "Temporary folder: ");
        $sb->add_text_option("archive_extract_command", "<br>Extraction command: ");
        $sb->add_label("<br>%f for archive, %d for temporary directory");
    }

    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->supported_mime($event->mime)) {
            global $config, $page;
            $tmpdir = shm_tempnam("archive");
            unlink($tmpdir);
            mkdir($tmpdir, 0755, true);
            $cmd = $config->get_string('archive_extract_command');
            $cmd = str_replace('%f', $event->tmpname, $cmd);
            $cmd = str_replace('%d', $tmpdir, $cmd);
            assert(is_string($cmd));
            exec($cmd);
            if (file_exists($tmpdir)) {
                try {
                    $results = add_dir($tmpdir, Tag::explode($event->metadata['tags']));
                    foreach ($results as $r) {
                        if(is_a($r, UploadError::class)) {
                            $page->flash($r->name." failed: ".$r->error);
                        }
                        if(is_a($r, UploadSuccess::class)) {
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
