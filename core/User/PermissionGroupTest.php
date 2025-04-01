<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class PermissionGroupTest extends TestCase
{
    public function testTitle(): void
    {
        $group = new MyExamplePermission();
        self::assertEquals("My Example", $group->get_title());
    }
}
