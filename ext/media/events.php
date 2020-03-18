<?php declare(strict_types=1);

class MediaResizeEvent extends Event
{
    public $engine;
    public $input_path;
    public $input_type;
    public $output_path;
    public $target_format;
    public $target_width;
    public $target_height;
    public $target_quality;
    public $minimize;
    public $ignore_aspect_ratio;
    public $allow_upscale;

    public function __construct(
        String $engine,
        string $input_path,
        string $input_type,
        string $output_path,
        int $target_width,
        int $target_height,
        bool $ignore_aspect_ratio = false,
        string $target_format = null,
        int $target_quality = 80,
        bool $minimize = false,
        bool $allow_upscale = true
    ) {
        parent::__construct();
        assert(in_array($engine, MediaEngine::ALL));
        $this->engine = $engine;
        $this->input_path = $input_path;
        $this->input_type = $input_type;
        $this->output_path = $output_path;
        $this->target_height = $target_height;
        $this->target_width = $target_width;
        $this->target_format = $target_format;
        $this->target_quality = $target_quality;
        $this->minimize = $minimize;
        $this->ignore_aspect_ratio = $ignore_aspect_ratio;
        $this->allow_upscale = $allow_upscale;
    }
}

class MediaCheckPropertiesEvent extends Event
{
    public $image;
    public $file_name;
    public $ext;

    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
        $this->file_name = warehouse_path(Image::IMAGE_DIR, $image->hash);
        $this->ext = strtolower($image->ext);
    }
}
