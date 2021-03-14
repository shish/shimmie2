<?php declare(strict_types=1);

/**
 * Class EmoticonList
 */
class EmoticonList extends Extension
{
    /** @var EmoticonListTheme */
    protected ?Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("emote/list")) {
            $this->theme->display_emotes(glob("ext/emoticons/default/*"));
        }
    }
}
