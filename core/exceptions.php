<?php declare(strict_types=1);

/**
 * Class SCoreException
 *
 * A base exception to be caught by the upper levels.
 */
class SCoreException extends RuntimeException
{
    /** @var string|null */
    public $query;

    /** @var string */
    public $error;

    public function __construct(string $msg, ?string $query=null)
    {
        parent::__construct($msg);
        $this->error = $msg;
        $this->query = $query;
    }
}

/**
 * Class PermissionDeniedException
 *
 * A fairly common, generic exception.
 */
class PermissionDeniedException extends SCoreException
{
}

/**
 * Class ImageDoesNotExist
 *
 * This exception is used when an Image cannot be found by ID.
 *
 * Example: Image::by_id(-1) returns null
 */
class ImageDoesNotExist extends SCoreException
{
}

/*
 * For validate_input()
 */
class InvalidInput extends SCoreException
{
}

/*
 * This is used by the image resizing code when there is not enough memory to perform a resize.
 */
class InsufficientMemoryException extends SCoreException
{
}

/*
 * This is used by the image resizing code when there is an error while resizing
 */
class ImageResizeException extends SCoreException
{
}
