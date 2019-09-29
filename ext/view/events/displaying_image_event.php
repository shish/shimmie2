<?php

/*
 * DisplayingImageEvent:
 *   $image -- the image being displayed
 *   $page  -- the page to display on
 *
 * Sent when an image is ready to display. Extensions who
 * wish to appear on the "view" page should listen for this,
 * which only appears when an image actually exists.
 */
class DisplayingImageEvent extends Event
{
    /** @var Image  */
    public $image;

    public $title;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    public function get_image(): Image
    {
        return $this->image;
    }

    public function set_title(String $title)
    {
        $this->title = $title;
    }
}
