<?php

declare(strict_types=1);

namespace Shimmie2;

final class StaticFilesTest extends ShimmiePHPUnitTestCase
{
    public function testStaticHandler(): void
    {
        self::get_page('favicon.ico');
        self::assert_response(200);
    }

    public function testDiffStyleSheetUpToDate(): void
    {
        $css = \Safe\file_get_contents(__DIR__ . "/style.css");
        $start = "/* start of DiffHelper::getStyleSheet() */\n";
        $end = "/* end of DiffHelper::getStyleSheet() */";
        $start_pos = strpos($css, $start);
        $end_pos = strpos($css, $end);
        self::assertNotFalse($start_pos, "Start marker not found in style.css");
        self::assertNotFalse($end_pos, "End marker not found in style.css");
        $block = substr($css, $start_pos + strlen($start), $end_pos - $start_pos - strlen($start));
        self::assertEquals(
            \Jfcherng\Diff\DiffHelper::getStyleSheet(),
            $block,
            "DiffHelper::getStyleSheet() output has changed, update the block in ext/static_files/style.css"
        );
    }
}
