<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArchiveFileHandler extends DataHandlerExtension
{
    public const KEY = "handle_archive";
    public const SUPPORTED_MIME = [MimeType::ZIP];

    #[EventListener]
    public function onDataUpload(DataUploadEvent $event): void
    {
        if ($this->supported_mime($event->mime)) {
            $tmpdir = shm_tempdir("archive");
            $cmd = Ctx::$config->get(ArchiveFileHandlerConfig::EXTRACT_COMMAND);
            $cmd = str_replace('"%f"', "%f", $cmd);
            $cmd = str_replace('"%d"', "%d", $cmd);
            $parts = explode(" ", $cmd);

            $command = new CommandBuilder($parts[0]);
            foreach (array_splice($parts, 1) as $part) {
                match($part) {
                    "%f" => $command->add_args($event->tmpname->str()),
                    "%d" => $command->add_args($tmpdir->str()),
                    default => $command->add_args($part),
                };
            }
            $command->execute();

            if ($tmpdir->exists()) {
                try {
                    $results = send_event(new DirectoryUploadEvent($tmpdir, Tag::explode($event->metadata->req('tags'))))->results;
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

    protected function media_check_properties(Image $image): ?MediaProperties
    {
        return null;
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
