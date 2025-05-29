<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, IMG, joinHTML};

class PixelFileHandlerTheme extends Themelet
{
    public function display_image(Image $image): void
    {
        $html = IMG([
            'alt' => 'main image',
            'class' => 'shm-main-image shm-click-to-scale',
            'id' => 'main_image',
            'src' => $image->get_image_link(),
            'data-width' => $image->width,
            'data-height' => $image->height,
            'data-mime' => $image->get_mime(),
            'onerror' => "shm_log('Error loading >>{$image->id}')",
        ]);
        Ctx::$page->add_block(new Block(null, $html, "main", 10));
    }

    public function display_metadata(Image $image): void
    {
        if (function_exists("exif_read_data")) {
            # FIXME: only read from jpegs?
            $exif = @exif_read_data($image->get_image_filename()->str(), "IFD0", true);
            if ($exif) {
                $info = [];
                foreach ($exif as $key => $section) {
                    foreach ($section as $name => $val) {
                        if ($key === "IFD0") {
                            // Cheap fix for array'd values in EXIF-data
                            if (is_array($val)) {
                                $val = implode(',', $val);
                            }
                            $info[] = "$name: $val";
                        }
                    }
                }
                if ($info) {
                    Ctx::$page->add_block(new Block("EXIF Info", joinHTML(BR(), $info), "left"));
                }
            }
        }
    }
}
