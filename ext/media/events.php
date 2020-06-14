<?php declare(strict_types=1);

class MediaResizeEvent extends Event
{
    public $engine;
    public $input_path;
    public $input_mime;
    public $output_path;
    public $target_mime;
    public $target_width;
    public $target_height;
    public $target_quality;
    public $alpha_color;
    public $minimize;
    public $allow_upscale;
    public $resize_type;

    public function __construct(
        String $engine,
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
    public $image;
    public $file_name;
    public $mime;

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
        $this->file_name = warehouse_path(Image::IMAGE_DIR, $image->hash);
        $this->mime = strtolower($image->get_mime());
    }
}
