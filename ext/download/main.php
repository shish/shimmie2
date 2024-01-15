<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events.php";

class Download extends Extension
{
    public function get_priority(): int
    {
        // Set near the end to give everything else a chance to process
        return 99;
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        global $page;

        $page->set_mime($event->mime);
        $page->set_mode(PageMode::FILE);
        $page->set_file($event->path, $event->file_modified);
        $event->stop_processing = true;
    }
}
