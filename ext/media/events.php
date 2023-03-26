<?php

declare(strict_types=1);

namespace Shimmie2;

class MediaResizeEvent extends Event
{
    public string $engine;
    public string $input_path;
    public string $input_mime;
    public string $output_path;
    public ?string $target_mime;
    public int $target_width;
    public int $target_height;
    public int $target_quality;
    public string $alpha_color;
    public bool $minimize;
    public bool $allow_upscale;
    public string $resize_type;

    public function __construct(
        string $engine,
        string $input_path,
        string $input_mime,
        string $output_path,
        int $target_width,
        int $target_height,
        string $resize_type = Media::RESIZE_TYPE_FIT,
        string $target_mime = null,
        string $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR,
        int $target_quality = 80,
        bool $minimize = false,
        bool $allow_upscale = true
    ) {
        parent::__construct();
        assert(in_array($engine, MediaEngine::ALL));
        $this->engine = $engine;
        $this->input_path = $input_path;
        $this->input_mime = $input_mime;
        $this->output_path = $output_path;
        $this->target_height = $target_height;
        $this->target_width = $target_width;
        $this->target_mime = $target_mime;
        if (empty($alpha_color)) {
            $alpha_color = Media::DEFAULT_ALPHA_CONVERSION_COLOR;
        }
        $this->alpha_color = $alpha_color;
        $this->target_quality = $target_quality;
        $this->minimize = $minimize;
        $this->allow_upscale = $allow_upscale;
        $this->resize_type = $resize_type;
    }
}

class MediaCheckPropertiesEvent extends Event
{
    public function __construct(public Image $image)
    {
        parent::__construct();
    }
}
