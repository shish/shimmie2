<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomSetupTheme extends SetupTheme
{
    protected function sb_to_html(SetupBlock $block): string
    {
        $html = parent::sb_to_html($block);
        return "<div class='tframe'>$html</div>";
    }
}
