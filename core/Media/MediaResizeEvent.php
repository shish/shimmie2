<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaResizeEvent extends Event
{
    /**
     * @param positive-int $target_width
     * @param positive-int $target_height
     */
    public function __construct(
        public MediaEngine $engine,
        public Path $input_path,
        public MimeType $input_mime,
        public Path $output_path,
        public int $target_width,
        public int $target_height,
        public ResizeType $resize_type = ResizeType::FIT,
        public ?MimeType $target_mime = null,
        public ?string $alpha_color = null,
        public int $target_quality = 80,
        public bool $minimize = false,
        public bool $allow_upscale = true
    ) {
        parent::__construct();
        if (empty($alpha_color)) {
            $this->alpha_color = Ctx::$config->get(ThumbnailConfig::ALPHA_COLOR);
        }
    }
}
