<?php


/**
 * Class Emoticons
 */
class Emoticons extends FormatterExtension
{
    public function format(string $text): string
    {
        $data_href = get_base_href();
        $text = preg_replace("/:([a-z]*?):/s", "<img src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
        return $text;
    }

    public function strip(string $text): string
    {
        return $text;
    }
}

/**
 * Class EmoticonList
 */
class EmoticonList extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("emote/list")) {
            $this->theme->display_emotes(glob("ext/emoticons/default/*"));
        }
    }
}
