<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A common base class for text formatting extensions
 *
 * format()
 *   should take the input text and return an HTML string, eg
 *   "[b]bold[/b]" -> "<b>bold</b>"
 *
 * strip()
 *   should take the input text and return a plain text string, eg
 *   "[b]bold[/b]" -> "bold"
 */
abstract class FormatterExtension extends Extension
{
    #[EventListener]
    public function onTextFormatting(TextFormattingEvent $event): void
    {
        $event->formatted = $this->format($event->formatted);
        $event->stripped  = $this->strip($event->stripped);
    }

    abstract public function format(string $text): string;
    abstract public function strip(string $text): string;
}
