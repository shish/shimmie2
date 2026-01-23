<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<EmoticonListTheme> */
final class EmoticonList extends Extension
{
    public const KEY = "emoticons_list";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("emote/list")) {
            $this->theme->display_emotes(\Safe\glob("ext/emoticons/default/*"));
        }
    }
}
