<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageAdminBlockBuildingEvent extends Event
{
    /** @var string[] */
    public array $parts = [];
    public Image $image;
    public User $user;
    public string $context;

    public function __construct(Image $image, User $user, string $context)
    {
        parent::__construct();
        $this->image = $image;
        $this->user = $user;
        $this->context = $context;
    }

    public function add_part(string $html, int $position=50)
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }
}
