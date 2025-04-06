<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{P, emptyHTML};

class MediaTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P('Search for posts based on the type of media.'),
            SHM_COMMAND_EXAMPLE('content:audio', 'Returns posts that contain audio, including videos and audio files.'),
            SHM_COMMAND_EXAMPLE('content:video', 'Returns posts that contain video, including animated GIFs.'),
        );
    }
}
