<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class QRImageTheme extends Themelet
{
    public function links_block(string $link): void
    {
        global $page;

        $page->add_block(new Block(
            "QR Code",
            rawHTML("<img alt='QR Code' src='//chart.apis.google.com/chart?chs=150x150&amp;cht=qr&amp;chl={$link}' />"),
            "left",
            50
        ));
    }
}
