<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, P, emptyHTML};

use MicroHTML\HTMLElement;

class MediaTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P('Search for posts based on the type of media.'),
            SHM_COMMAND_EXAMPLE('content=audio', 'Returns posts that contain audio, including videos and audio files.'),
            SHM_COMMAND_EXAMPLE('content=video', 'Returns posts that contain video, including animated GIFs.'),
            //
            BR(),
            P("Searching by dimentions."),
            SHM_COMMAND_EXAMPLE("size=640x480", "Returns posts exactly 640 pixels wide by 480 pixels high."),
            SHM_COMMAND_EXAMPLE("size>1920x1080", "Returns posts with a width larger than 1920 and a height larger than 1080."),
            SHM_COMMAND_EXAMPLE("width=1000", "Returns posts exactly 1000 pixels wide."),
            SHM_COMMAND_EXAMPLE("height=1000", "Returns posts exactly 1000 pixels high."),
            SHM_COMMAND_EXAMPLE("ratio=4:3", "Returns posts with an aspect ratio of 4:3."),
            SHM_COMMAND_EXAMPLE("ratio>16:9", "Returns posts with an aspect ratio greater than 16:9."),
            //
            BR(),
            P("Searching posts by media length."),
            P("Available suffixes are ms, s, m, h, d, and y. A number by itself will be interpreted as milliseconds. Searches using = are not likely to work unless time is specified down to the millisecond."),
            SHM_COMMAND_EXAMPLE("length>=1h", "Returns posts that are longer than an hour."),
            SHM_COMMAND_EXAMPLE("length<=10h15m", "Returns posts that are shorter than 10 hours and 15 minutes."),
            SHM_COMMAND_EXAMPLE("length>=10000", "Returns posts that are longer than 10,000 milliseconds, or 10 seconds."),
        );
    }
}
