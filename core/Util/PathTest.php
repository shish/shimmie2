<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    public function testAbsolute(): void
    {
        $path = new Path("/home/user");
        $absolute = $path->absolute();
        $this->assertEquals("/home/user", $absolute->str());
    }

    public function testAbsoluteRel(): void
    {
        $path = new Path("core/Util/PathTest.php");
        $absolute = $path->absolute();
        $this->assertEquals(__FILE__, $absolute->str());
    }

    public function testRelative(): void
    {
        $base = new Path("/home/user/");
        $path = new Path("/home/user/documents");
        $relative = $path->relative_to($base);
        $this->assertEquals("documents", $relative->str());
    }

    public function testRelativeWithParent(): void
    {
        $base = new Path("/home/user/");
        $path = new Path("/home/user/documents/subfolder");
        $relative = $path->relative_to($base);
        $this->assertEquals("documents/subfolder", $relative->str());
    }

    public function testRelativeWithSibling(): void
    {
        $base = new Path("/home/user/documents/");
        $path = new Path("/home/user/cakes");
        $relative = $path->relative_to($base);
        $this->assertEquals("../cakes", $relative->str());
    }
}
