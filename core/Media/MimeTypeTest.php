<?php

declare(strict_types=1);

namespace Shimmie2;

final class MimeTypeTest extends ShimmiePHPUnitTestCase
{
    public function test_create(): void
    {
        $m = new MimeType("image/png");
        self::assertSame("image/png", $m->base);
        // self::assertSame("image", $m->type);
        // self::assertSame("png", $m->subtype);
        self::assertSame("image/png", (string)$m);
        self::assertSame([], $m->parameters);
    }

    public function test_create_with_params(): void
    {
        $m = new MimeType("text/html; charset=UTF-8");
        self::assertSame("text/html", $m->base);
        self::assertSame(["charset" => "UTF-8"], $m->parameters);
        self::assertSame("text/html; charset=UTF-8", (string)$m);

        // space is optional
        $m = new MimeType("text/html;charset=UTF-8;tasty");
        self::assertSame("text/html", $m->base);
        self::assertSame(["charset" => "UTF-8", "tasty" => ''], $m->parameters);
        self::assertSame("text/html; charset=UTF-8; tasty", (string)$m);
    }

    public function test_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType("invalid-mime-type");
    }
}
