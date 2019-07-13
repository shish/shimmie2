<?php

class ImageInfoBoxBuildingEvent extends Event
{
    /** @var array  */
    public $parts = [];
    /** @var Image  */
    public $image;
    /** @var User  */
    public $user;

    public function __construct(Image $image, User $user)
    {
        $this->image = $image;
        $this->user = $user;
    }

    public function add_part(string $html, int $position=50)
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}