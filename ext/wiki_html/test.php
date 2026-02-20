<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiHtmlTest extends ShimmiePHPUnitTestCase
{
    public function testHtmlRendering(): void
    {
        $escaped_content = "[html]&lt;div class=\"wiki-container\"&gt;Content&lt;/div&gt;[/html]";

        $event = new TextFormattingEvent($escaped_content);
        $event->formatted = $escaped_content;

        send_event($event);

        self::assertStringContainsString('<div class="wiki-container">', $event->formatted);
        self::assertStringNotContainsString('&lt;', $event->formatted);
    }
}
