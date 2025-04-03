<?php

declare(strict_types=1);

namespace Shimmie2;

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
