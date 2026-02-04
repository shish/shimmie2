<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT};

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
    public function __construct(
        public Image $image
    ) {
        parent::__construct();
    }
}

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


/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class ImageInfoBoxBuildingEvent extends PartListBuildingEvent
{
    /** @var HTMLElement[] */
    private array $sidebar_parts = [];

    public function __construct(
        public Image $image,
        public User $user
    ) {
        parent::__construct();
    }

    /**
     * Add content to the right-hand sidebar of the info box
     */
    public function add_sidebar_part(HTMLElement $html, int $position = 50): void
    {
        while (isset($this->sidebar_parts[$position])) {
            $position++;
        }
        $this->sidebar_parts[$position] = $html;
    }

    /**
     * @return array<HTMLElement>
     */
    public function get_sidebar_parts(): array
    {
        ksort($this->sidebar_parts);
        return $this->sidebar_parts;
    }
}

class ImageInfoSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public int $slot,
        public QueryArray $params
    ) {
        parent::__construct();
    }

    /**
     * Get a slot-specific value, or a common value, or null. This allows
     * a user to POST an update to multiple images at once, setting eg
     * "source" to be a common default source and "source12" to be a
     * specific source for image 12.
     *
     * Specifically we check for "empty" rather than "isset" because
     * the upload form might have a "source" field that is empty, and
     * we want to allow non-empty values to override empty ones.
     */
    public function get_param(string $name): ?string
    {
        if (!empty($this->params["$name{$this->slot}"])) {
            return $this->params["$name{$this->slot}"];
        }
        if (!empty($this->params[$name])) {
            return $this->params[$name];
        }
        return null;
    }
}
