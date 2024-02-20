<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class EmoticonList
 */
class EmoticonList extends Extension
{
    /** @var EmoticonListTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("emote/list")) {
            $this->theme->display_emotes(\Safe\glob("ext/emoticons/default/*"));
        }
    }
}
