<?php

declare(strict_types=1);

namespace Shimmie2;

final class MimeSystemTest extends ShimmiePHPUnitTestCase
{
    public function testJPEG(): void
    {
        $result = MimeType::get_for_file(new Path("tests/bedroom_workshop.jpg"));
        self::assertEquals(MimeType::JPEG, $result);
    }
}
