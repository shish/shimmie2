<?php

declare(strict_types=1);

namespace Shimmie2;

class HelpPagesTest extends ShimmiePHPUnitTestCase
{
    public function test_list(): void
    {
        send_event(new HelpPageListBuildingEvent());
        $this->assertTrue(true);
    }

    public function test_page(): void
    {
        send_event(new HelpPageBuildingEvent("test"));
        $this->assertTrue(true);
    }
}
