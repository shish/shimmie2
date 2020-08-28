<?php

require_once "controllers/tags_controller.php";
require_once "controllers/images_controller.php";

class ApiInternal extends Extension
{
    private const INTERNAL_API = "api/internal/";

    public const TAGS_API_PATH = self::INTERNAL_API."tags/";
    public const TAGS_BY_IMAGE_PATH = self::TAGS_API_PATH."by_image";

    public const IMAGE_API_PATH = self::INTERNAL_API."image/";


    private $tags;
    private $images;

    public function __construct($class = null)
    {
        parent::__construct();

        $this->tags = new ApiTagsController();
        $this->images = new ApiImagesController();
    }

    public function get_priority(): int
    {
        return 30;
    } // before Home

    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->args[0]==="api"&&$event->args[1]==="internal") {
            $controller_name = $event->args[2];
            $controller = null;
            switch ($controller_name) {
                case "tags":
                    $controller = $this->tags;
                    break;
                case "image":
                    $controller = $this->images;
                    break;
            }

            if ($controller!==null) {
                $controller->process($event->args);
            } else {
                throw new SCoreException("Controller not found for $controller_name");
            }
        }
    }
}
