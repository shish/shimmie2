<?php

declare(strict_types=1);

namespace Shimmie2;

final class AkismetTest extends ShimmiePHPUnitTestCase
{
    public function testAkismetNotConfigured(): void
    {
        // When Akismet is not configured, it should not block anything
        Ctx::$config->set("akismet_api_key", "");
        $e = send_event(new CheckStringContentEvent("This is a test comment"));
        self::assertEquals("text", $e->type->value);
    }
}
