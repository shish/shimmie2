<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, OPTION, SELECT};

class TranscodeImageTheme extends Themelet
{
    /**
     * Display a link to resize an image
     *
     * @param array<string, ?MimeType> $options
     */
    public function get_transcode_html(Image $image, array $options): \MicroHTML\HTMLElement
    {
        return SHM_FORM(
            action: make_link("transcode/{$image->id}"),
            onsubmit: "return transcodeSubmit()",
            children: [
                INPUT(["type" => "hidden", "id" => "image_lossless", "name" => "image_lossless", "value" => $image->lossless ? "true" : "false"]),
                $this->get_transcode_picker_html($options),
                SHM_SUBMIT("Transcode Image"),
            ]
        );
    }

    /**
     * @param array<string, ?MimeType> $options
     */
    public function get_transcode_picker_html(array $options): \MicroHTML\HTMLElement
    {
        $select = SELECT([
            "id" => "transcode_mime",
            "name" => "transcode_mime",
            "required" => true,
        ]);
        foreach ($options as $display => $value) {
            $select->appendChild(OPTION(["value" => $value ?? ""], $display));
        }
        return $select;
    }
}
