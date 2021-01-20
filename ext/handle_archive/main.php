<?php declare(strict_types=1);

class ArchiveFileHandler extends DataHandlerExtension
{
    protected $SUPPORTED_MIME = [MimeType::ZIP];

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string('archive_extract_command', 'unzip -d "%d" "%f"');
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Archive Handler Options");
        $sb->add_text_option("archive_tmp_dir", "Temporary folder: ");
        $sb->add_text_option("archive_extract_command", "<br>Extraction command: ");
        $sb->add_label("<br>%f for archive, %d for temporary directory");
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        if ($this->supported_mime($event->mime)) {
            global $config, $page;
            $tmp = sys_get_temp_dir();
            $tmpdir = "$tmp/shimmie-archive-{$event->hash}";
            $cmd = $config->get_string('archive_extract_command');
            $cmd = str_replace('%f', $event->tmpname, $cmd);
            $cmd = str_replace('%d', $tmpdir, $cmd);
            exec($cmd);
            if (file_exists($tmpdir)) {
                try {
                    $results = add_dir($tmpdir);
                    if (count($results) > 0) {
                        $page->flash("Adding files" . implode("\n", $results));
                    }
                } finally {
                    deltree($tmpdir);
                }
                $event->image_id = -2; // default -1 = upload wasn't handled
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
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

    protected function create_thumb(string $hash, string $type): bool
    {
        return false;
    }
}
