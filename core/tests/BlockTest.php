<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

require_once "core/block.php";

class BlockTest extends TestCase
{
    public function test_basic(): void
    {
        $b = new Block("head", "body");
        $this->assertEquals(
            "<section id='headmain'><h3 data-toggle-sel='#headmain' class=''>head</h3><div class='blockbody'>body</div></section>\n",
            $b->get_html()
        );
    }
}
