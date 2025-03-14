<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{A};

/**
 * Class Block
 *
 * A basic chunk of a page.
 */
class Block
{
    /**
     * The block's title.
     */
    public ?string $header;

    /**
     * The content of the block.
     */
    public HTMLElement $body;

    /**
     * Where the block should be placed. The default theme supports
     * "main" and "left", other themes can add their own areas.
     */
    public string $section;

    /**
     * How far down the section the block should appear, higher
     * numbers appear lower. The scale is 0-100 by convention,
     * though any number will work.
     */
    public int $position;

    /**
     * A unique ID for the block.
     */
    public string $id;

    /**
     * Should this block count as content for the sake of
     * the 404 handler
     */
    public bool $is_content = true;

    public function __construct(?string $header, HTMLElement $body, string $section = "main", int $position = 50, ?string $id = null)
    {
        $this->header = $header;
        $this->body = $body;
        $this->section = $section;
        $this->position = $position;

        if (is_null($id)) {
            $id = (empty($header) ? 'unknown' : $header) . $section;
        }
        $str_id = \Safe\preg_replace('/[^\w-]/', '', str_replace(' ', '_', $id));
        $this->id = $str_id;
    }

    public static function nav(): self
    {
        return new self("Navigation", A(["href" => make_link()], "Index"), "left", 0);
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
