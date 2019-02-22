<?php

/**
 * An image is being added to the database.
 */
class ImageAdditionEvent extends Event {
	/** @var User */
	public $user;

	/** @var Image */
	public $image;

	/**
	 * Inserts a new image into the database with its associated
	 * information. Also calls TagSetEvent to set the tags for
	 * this new image.
	 *
	 * @see TagSetEvent
	 * @param Image $image The new image to add.
	 */
	public function __construct(Image $image) {
		$this->image = $image;
	}
}

class ImageAdditionException extends SCoreException {
	public $error;

	public function __construct(string $error) {
		$this->error = $error;
	}
}

/**
 * An image is being deleted.
 */
class ImageDeletionEvent extends Event {
	/** @var \Image */
	public $image;

	/**
	 * Deletes an image.
	 *
	 * Used by things like tags and comments handlers to
	 * clean out related rows in their tables.
	 *
	 * @param Image $image The image being deleted.
	 */
	public function __construct(Image $image) {
		$this->image = $image;
	}
}

/**
 * An image is being replaced.
 */
class ImageReplaceEvent extends Event {
	/** @var int */
	public $id;
	/** @var \Image */
	public $image;

	/**
	 * Replaces an image.
	 *
	 * Updates an existing ID in the database to use a new image
	 * file, leaving the tags and such unchanged. Also removes
	 * the old image file and thumbnail from the disk.
	 *
	 * @param int $id The ID of the image to replace.
	 * @param Image $image The image object of the new image to use.
	 */
	public function __construct(int $id, Image $image) {
		$this->id = $id;
		$this->image = $image;
	}
}

class ImageReplaceException extends SCoreException {
	/** @var string */
	public $error;

	public function __construct(string $error) {
		$this->error = $error;
	}
}

/**
 * Request a thumbnail be made for an image object.
 */
class ThumbnailGenerationEvent extends Event {
	/** @var string */
	public $hash;
	/** @var string */
	public $type;
	/** @var bool */
	public $force;

	/**
	 * Request a thumbnail be made for an image object
	 *
	 * @param string $hash The unique hash of the image
	 * @param string $type The type of the image
	 * @param bool $force Regenerate the thumbnail even if one already exists
	 */
	public function __construct(string $hash, string $type, bool $force=false) {
		$this->hash = $hash;
		$this->type = $type;
		$this->force = $force;
	}
}


/*
 * ParseLinkTemplateEvent:
 *   $link     -- the formatted link
 *   $original -- the formatting string, for reference
 *   $image    -- the image who's link is being parsed
 */
class ParseLinkTemplateEvent extends Event {
	/** @var string */
	public $link;
	/** @var string */
	public $original;
	/** @var \Image */
	public $image;

	/**
	 * @param string $link The formatted link
	 * @param Image $image The image who's link is being parsed
	 */
	public function __construct(string $link, Image $image) {
		$this->link = $link;
		$this->original = $link;
		$this->image = $image;
	}

	public function replace(string $needle, string $replace) {
		$this->link = str_replace($needle, $replace, $this->link);
	}
}
