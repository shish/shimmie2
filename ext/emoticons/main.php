<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Class Emoticons
 */
final class Emoticons extends FormatterExtension
{
    public const KEY = "emoticons";

    public function format(string $text): string
    {
        $data_href = Url::base();
        $text = \Safe\preg_replace("/:([a-z]*?):/s", "<img alt='\1' src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
        return $text;
    }

    public function strip(string $text): string
    {
        return $text;
    }
}
