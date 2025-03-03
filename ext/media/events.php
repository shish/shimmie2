<?php

declare(strict_types=1);

namespace Shimmie2;

class MediaResizeEvent extends Event
{
    /**
     * @param positive-int $target_width
     * @param positive-int $target_height
     */
    public function __construct(
        public string $engine,
        public string $input_path,
        public string $input_mime,
        public string $output_path,
        public int $target_width,
        public int $target_height,
        public string $resize_type = Media::RESIZE_TYPE_FIT,
        public ?string $target_mime = null,
        public string $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR,
        public int $target_quality = 80,
        public bool $minimize = false,
        public bool $allow_upscale = true
    ) {
        parent::__construct();
        assert(in_array($engine, MediaEngine::ALL));
        if (empty($alpha_color)) {
            $this->alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR;
        }
    }
}

class MediaCheckPropertiesEvent extends Event
{
    public function __construct(
        public Image $image
    ) {
        parent::__construct();
    }
}
