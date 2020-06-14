<?php declare(strict_types=1);

/**
 * An image is being added to the database.
 */
class ImageAdditionEvent extends Event
{
    /** @var User */
    public $user;

    /** @var Image */
    public $image;

    public $merged = false;

    /**
     * Inserts a new image into the database with its associated
     * information. Also calls TagSetEvent to set the tags for
     * this new image.
     */
    public function __construct(Image $image)
    {
        parent::__construct();
        $this->image = $image;
    }
}

class ImageAdditionException extends SCoreException
{
}

/**
 * An image is being deleted.
 */
class ImageDeletionEvent extends Event
{
    /** @var Image */
    public $image;

    /** @var bool */
    public $force = false;

    /**
     * Deletes an image.
     *
     * Used by things like tags and comments handlers to
     * clean out related rows in their tables.
     */
    public function __construct(Image $image, bool $force = false)
    {
        parent::__construct();
        $this->image = $image;
        $this->force = $force;
    }
}

/**
 * An image is being replaced.
 */
class ImageReplaceEvent extends Event
{
    /** @var int */
    public $id;
    /** @var Image */
    public $image;

    /**
     * Replaces an image.
     *
     * Updates an existing ID in the database to use a new image
     * file, leaving the tags and such unchanged. Also removes
     * the old image file and thumbnail from the disk.
     */
    public function __construct(int $id, Image $image)
    {
        parent::__construct();
        $this->id = $id;
        $this->image = $image;
    }
}

class ImageReplaceException extends SCoreException
{
}

/**
 * Request a thumbnail be made for an image object.
 */
class ThumbnailGenerationEvent extends Event
{
    /** @var string */
    public $hash;
    /** @var string */
    public $mime;
    /** @var bool */
    public $force;

    /** @var bool */
    public $generated;

    /**
     * Request a thumbnail be made for an image object
     */
    public function __construct(string $hash, string $mime, bool $force=false)
    {
        parent::__construct();
        $this->hash = $hash;
        $this->mime = $mime;
        $this->force = $force;
        $this->generated = false;
    }
}


/*
 * ParseLinkTemplateEvent:
 *   $link     -- the formatted text (with each element URL Escape'd)
 *   $text     -- the formatted text (not escaped)
 *   $original -- the formatting string, for reference
 *   $image    -- the image who's link is being parsed
 */
class ParseLinkTemplateEvent extends Event
{
    /** @var string */
    public $link;
    /** @var string */
    public $text;
    /** @var string */
    public $original;
    /** @var Image */
    public $image;

    public function __construct(string $link, Image $image)
    {
        parent::__construct();
        $this->link = $link;
        $this->text = $link;
        $this->original = $link;
        $this->image = $image;
    }

    public function replace(string $needle, ?string $replace): void
    {
        if (!is_null($replace)) {
            $this->link = str_replace($needle, url_escape($replace), $this->link);
            $this->text = str_replace($needle, $replace, $this->text);
        }
    }
}
