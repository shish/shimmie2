<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{FORM,INPUT};

class ImageAdminBlockBuildingEvent extends Event
{
    /** @var HTMLElement[] */
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

    public function add_part(HTMLElement $html, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = $html;
    }

    public function add_button(string $name, string $path, int $position = 50): void
    {
        $this->add_part(
            SHM_SIMPLE_FORM(
                $path,
                INPUT([
                    "type" => "submit",
                    "value" => $name,
                ])
            ),
            $position
        );
    }
}
