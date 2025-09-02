<?php

declare(strict_types=1);

namespace Shimmie2;

final class MimeTypeTest extends ShimmiePHPUnitTestCase
{
    public function test_create(): void
    {
        $m = new MimeType("image/png");
        self::assertEquals("image/png", $m->base);
        // self::assertEquals("image", $m->type);
        // self::assertEquals("png", $m->subtype);
        self::assertEquals("image/png", (string)$m);
        self::assertEquals([], $m->parameters);
    }

    public function test_create_with_params(): void
    {
        $m = new MimeType("text/html; charset=UTF-8");
        self::assertEquals("text/html", $m->base);
        self::assertEquals(["charset" => "UTF-8"], $m->parameters);
        self::assertEquals("text/html; charset=UTF-8", (string)$m);

        // space is optional
        $m = new MimeType("text/html;charset=UTF-8;tasty");
        self::assertEquals("text/html", $m->base);
        self::assertEquals(["charset" => "UTF-8", "tasty" => ''], $m->parameters);
        self::assertEquals("text/html; charset=UTF-8; tasty", (string)$m);
    }

    public function test_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType("invalid-mime-type");
    }
}
