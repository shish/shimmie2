<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events.php";

final class Download extends Extension
{
    public const KEY = "download";

    #[EventListener(priority: 99)] // Set near the end to give everything else a chance to process
    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        Ctx::$page->set_file($event->mime, $event->path, $event->file_modified);
        $event->stop_processing = true;
    }
}
