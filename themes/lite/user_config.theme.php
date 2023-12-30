<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomUserConfigTheme extends UserConfigTheme
{
    protected function sb_to_html(SetupBlock $block): string
    {
        $html = parent::sb_to_html($block);
        return "<div class='tframe'>$html</div>";
    }
}
