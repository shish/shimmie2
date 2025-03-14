<?php

declare(strict_types=1);

namespace Shimmie2;

class StatisticsTest extends ShimmiePHPUnitTestCase
{
    public function testStatisticsPage(): void
    {
        $page = $this->get_page('stats');
        self::assert_title("Stats");
    }

    public function testTop100StatisticsPage(): void
    {
        $page = $this->get_page('stats/100');
        self::assert_title("Stats");
    }
}
