<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * ParseLinkTemplateEvent:
 *   $link     -- the formatted text (with each element URL Escape'd)
 *   $text     -- the formatted text (not escaped)
 *   $image    -- the image who's link is being parsed
 */
final class ParseLinkTemplateEvent extends Event
{
    public string $link;
    public string $text;
    public Image $image;

    public function __construct(string $link, Image $image)
    {
        parent::__construct();
        $this->link = $link;
        $this->text = $link;
        $this->image = $image;
    }

    public function replace(string $needle, ?string $replace): void
    {
        if (!is_null($replace)) {
            $this->link = str_replace($needle, url_escape($replace), $this->link);
            $this->text = str_replace($needle, $replace, $this->text);
        }
    }
}
