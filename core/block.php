<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{DIV, H3, SECTION, rawHTML};

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
    public ?string $body;

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

    public function __construct(?string $header, string|HTMLElement|null $body, string $section = "main", int $position = 50, ?string $id = null)
    {
        $this->header = $header;
        $this->body = (string)$body;
        $this->section = $section;
        $this->position = $position;

        if (is_null($id)) {
            $id = (empty($header) ? md5($this->body ?? '') : $header) . $section;
        }
        $str_id = preg_replace_ex('/[^\w-]/', '', str_replace(' ', '_', $id));
        $this->id = $str_id;
    }

    /**
     * Get the HTML for this block.
     */
    public function get_html(bool $hidable = false): HTMLElement
    {
        $block = SECTION(['id' => $this->id]);
        if (!empty($this->header)) {
            $block->appendChild(H3(["data-toggle-sel" => "#{$this->id}", "class" => $hidable ? "shm-toggler" : ""], $this->header));
        }
        if (!empty($this->body)) {
            $block->appendChild(DIV(['class' => "blockbody"], rawHTML($this->body)));
        }
        return $block;
    }
}


/**
 * Class NavBlock
 *
 * A generic navigation block with a link to the main page.
 *
 * Used because "new NavBlock()" is easier than "new Block('Navigation', ..."
 *
 */
class NavBlock extends Block
{
    public function __construct()
    {
        parent::__construct("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0);
    }
}
