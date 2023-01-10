<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class Emoticons
 */
class Emoticons extends FormatterExtension
{
    public function format(string $text): string
    {
        $data_href = get_base_href();
        $text = preg_replace("/:([a-z]*?):/s", "<img alt='\1' src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
        return $text;
    }

    public function strip(string $text): string
    {
        return $text;
    }
}
