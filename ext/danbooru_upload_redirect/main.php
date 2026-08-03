<?php

declare(strict_types=1);

namespace Shimmie2;

final class DanbooruUploadRedirect extends Extension
{
    public const KEY = "danbooru_upload_redirect";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        if ($event->page_matches("uploads/new")) {
            Ctx::$page->set_redirect((make_link("upload", ["url" => @$_GET['url'], "source" => @$_GET['ref']])));
        }
    }
}
