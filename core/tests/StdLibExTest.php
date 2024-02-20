<?php

declare(strict_types=1);

namespace Shimmie2;

class StdLibExTest extends ShimmiePHPUnitTestCase
{
    public function testJsonEncodeOk(): void
    {
        $this->assertEquals(
            '{"a":1,"b":2,"c":3,"d":4,"e":5}',
            \Safe\json_encode(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5])
        );
    }

    public function testJsonEncodeError(): void
    {
        $e = $this->assertException(\Exception::class, function () {
            \Safe\json_encode("\xB1\x31");
        });
        $this->assertEquals(
            "Malformed UTF-8 characters, possibly incorrectly encoded",
            $e->getMessage()
        );
    }
}
