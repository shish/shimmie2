<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * A signal that some text needs formatting, the event carries
 * both the text and the result
 */
final class TextFormattingEvent extends Event
{
    /**
     * with formatting applied
     */
    public string $formatted;

    /**
     * with formatting removed
     */
    public string $stripped;

    public function __construct(string $text)
    {
        parent::__construct();
        // We need to escape before formatting, instead of at display time,
        // because formatters will add their own HTML tags into the mix and
        // we don't want to escape those.
        $this->formatted = htmlentities(trim($text), ENT_QUOTES, "UTF-8");
        $this->stripped  = $text;
    }

    public function getFormattedHTML(): HTMLElement
    {
        return \MicroHTML\rawHTML($this->formatted);
    }
}
