<?php

declare(strict_types=1);

namespace Shimmie2;

class MimeSystemTest extends ShimmiePHPUnitTestCase
{
    public function testJPEG(): void
    {
        $result = MimeType::get_for_file("tests/bedroom_workshop.jpg");
        $this->assertEquals(MimeType::JPEG, $result);
    }
}
