<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events.php";

final class Download extends Extension
{
    public const KEY = "download";

    public function get_priority(): int
    {
        // Set near the end to give everything else a chance to process
        return 99;
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        Ctx::$page->set_file($event->mime, $event->path, $event->file_modified);
        $event->stop_processing = true;
    }
}
