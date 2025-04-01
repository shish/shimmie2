<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class BaseConfigGroupTest extends TestCase
{
    public function testTitle(): void
    {
        $group = new MyExampleConfig();
        self::assertEquals("My Example", $group->get_title());
    }
}
