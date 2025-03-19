<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;
use function MicroHTML\OPTION;
use function MicroHTML\SELECT;

class TranscodeVideoTheme extends Themelet
{
    /**
     * Display a link to resize an image
     *
     * @param array<string, string> $options
     */
    public function get_transcode_html(Image $image, array $options): \MicroHTML\HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("transcode_video/{$image->id}"),
            INPUT(["type" => "hidden", "name" => "codec", "value" => $image->video_codec]),
            $this->get_transcode_picker_html($options),
            SHM_SUBMIT("Transcode Video")
        );
    }

    /**
     * @param array<string, string> $options
     */
    public function get_transcode_picker_html(array $options): \MicroHTML\HTMLElement
    {
        $select = SELECT([
            "id" => "transcode_format",
            "name" => "transcode_format",
            "required" => true,
        ]);
        foreach ($options as $display => $value) {
            $select->appendChild(OPTION(["value" => $value], $display));
        }
        return $select;
    }
}
