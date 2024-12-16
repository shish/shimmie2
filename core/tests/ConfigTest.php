<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "core/imageboard/image.php";

class ConfigTest extends ShimmiePHPUnitTestCase
{
    public function testConfigGroup(): void
    {
        $conf = ConfigGroup::get_group_for_entry_by_name("comment_limit");
        $this->assertNotNull($conf);
        $this->assertEquals(CommentConfig::class, $conf::class);
    }
}
