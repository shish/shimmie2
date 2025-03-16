<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A signal that some text needs formatting, the event carries
 * both the text and the result
 */
final class TextFormattingEvent extends Event
{
    /**
     * For reference
     */
    public string $original;

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
        $h_text = html_escape(trim($text));
        $this->original  = $h_text;
        $this->formatted = $h_text;
        $this->stripped  = $h_text;
    }
}
