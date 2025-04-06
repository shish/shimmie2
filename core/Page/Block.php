<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A};

use MicroHTML\HTMLElement;

/**
 * Class Block
 *
 * A basic chunk of a page.
 */
class Block
{
    /**
     * @param ?string $header The block's title
     * @param HTMLElement $body The content of the block
     * @param string $section Where the block should be placed. The default theme supports
     * "main" and "left", other themes can add their own areas.
     * @param int $position How far down the section the block should appear, higher
     * numbers appear lower. The scale is 0-100 by convention,
     * though any number will work.
     * @param ?string $id A unique HTML ID for the block
     * @param bool $is_content Whether this block should be considered "content" for the 404 handler
     */
    public function __construct(
        public ?string $header,
        public HTMLElement $body,
        public string $section = "main",
        public int $position = 50,
        public ?string $id = null,
        public bool $is_content = true,
    ) {
        if (is_null($id)) {
            $id = (empty($header) ? 'unknown' : $header) . $section;
        }
        $str_id = \Safe\preg_replace('/[^\w-]/', '', str_replace(' ', '_', $id));
        $this->id = $str_id;
    }

    /**
     * Compare two Block objects, used to sort them before being displayed
     */
    public static function cmp(Block $a, Block $b): int
    {
        if ($a->position === $b->position) {
            if ($a->header && $b->header) {
                return strcasecmp($a->header, $b->header);
            }
            return 0;
        } else {
            return ($a->position > $b->position) ? 1 : -1;
        }
    }
}
