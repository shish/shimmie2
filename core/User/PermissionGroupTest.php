<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class MyExamplePermission extends PermissionGroup
{
}

final class PermissionGroupTest extends TestCase
{
    public function testTitle(): void
    {
        $group = new MyExamplePermission();
        self::assertEquals("My Example", $group->get_title());
    }
}
