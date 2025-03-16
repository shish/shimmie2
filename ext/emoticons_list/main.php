<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class EmoticonList
 */
final class EmoticonList extends Extension
{
    public const KEY = "emoticons_list";
    /** @var EmoticonListTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("emote/list")) {
            $this->theme->display_emotes(\Safe\glob("ext/emoticons/default/*"));
        }
    }
}
