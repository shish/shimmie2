<?php declare(strict_types=1);

/**
 * Class SCoreException
 *
 * A base exception to be caught by the upper levels.
 */
class SCoreException extends RuntimeException
{
    public ?string $query;
    public string $error;

    public function __construct(string $msg, ?string $query=null)
    {
        parent::__construct($msg);
        $this->error = $msg;
        $this->query = $query;
    }
}

class InstallerException extends RuntimeException
{
    public string $title;
    public string $body;
    public int $exit_code;

    public function __construct(string $title, string $body, int $exit_code)
    {
        parent::__construct($body);
        $this->title = $title;
        $this->body = $body;
        $this->exit_code = $exit_code;
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
