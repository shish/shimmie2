<?php

declare(strict_types=1);

namespace Shimmie2;

final class StatisticsTest extends ShimmiePHPUnitTestCase
{
    public function testStatisticsPage(): void
    {
        self::get_page('stats');
        self::assert_title("Stats");
    }

    public function testTop100StatisticsPage(): void
    {
        self::get_page('stats/100');
        self::assert_title("Stats");
    }
}
