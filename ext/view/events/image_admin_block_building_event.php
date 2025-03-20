<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT};

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class ImageAdminBlockBuildingEvent extends PartListBuildingEvent
{
    public function __construct(
        public Image $image,
        public User $user,
        public string $context
    ) {
        parent::__construct();
    }

    public function add_button(string $name, string $path, int $position = 50): void
    {
        $this->add_part(
            SHM_SIMPLE_FORM(
                make_link($path),
                INPUT([
                    "type" => "submit",
                    "value" => $name,
                ])
            ),
            $position
        );
    }
}
