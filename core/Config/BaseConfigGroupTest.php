<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

/**
 * BaseConfigGroup
 *  |- MockBaseConfigGroup
 *     |- MockSysConfigGroup
 *     |  |- FooMockSysConfigGroup
 *     |  |- BarMockSysConfigGroup
 *     |- MockUserConfigGroup
 *        |- BazMockUserConfigGroup
 *        |- QuxMockUserConfigGroup
 */
abstract class MockBaseConfigGroup extends BaseConfigGroup
{
}

abstract class MockSysConfigGroup extends MockBaseConfigGroup
{
}

class FooMockSysConfigGroup extends MockSysConfigGroup
{
    #[ConfigMeta("Foo ID", ConfigType::INT)]
    public const FOO_ID = "foo_id";
}

class BarMockSysConfigGroup extends MockSysConfigGroup
{
    #[ConfigMeta("Bar ID", ConfigType::INT)]
    public const BAR_ID = "bar_id";
}

abstract class MockUserConfigGroup extends MockBaseConfigGroup
{
}

class BazMockUserConfigGroup extends MockUserConfigGroup
{
    #[ConfigMeta("Baz ID", ConfigType::INT)]
    public const BAZ_ID = "baz_id";

}

class QuxMockUserConfigGroup extends MockUserConfigGroup
{
    #[ConfigMeta("Qux ID", ConfigType::INT)]
    public const QUX_ID = "qux_id";
}

final class BaseConfigGroupTest extends TestCase
{
    public function testTitle(): void
    {
        $group = new MyExampleConfig();
        self::assertSame("My Example", $group->get_title());
    }

    public function testGetAllMetas(): void
    {
        self::assertCount(4, MockBaseConfigGroup::get_all_metas());
        self::assertCount(2, MockSysConfigGroup::get_all_metas());
        self::assertCount(1, FooMockSysConfigGroup::get_all_metas());
    }
}
