<?php

declare(strict_types=1);

namespace Shimmie2;

class HelpPagesTest extends ShimmiePHPUnitTestCase
{
    public function test_list(): void
    {
        $e = send_event(new HelpPageListBuildingEvent());
        $this->assertGreaterThan(0, count($e->pages));
    }

    public function test_page(): void
    {
        $e = send_event(new HelpPageBuildingEvent("test"));
        $this->assertEquals(0, count($e->get_parts()));
    }
}
