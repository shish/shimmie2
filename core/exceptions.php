<?php

declare(strict_types=1);

/**
 * A base exception to be caught by the upper levels.
 */
class SCoreException extends RuntimeException
{
    public ?string $query;
    public string $error;
    public int $http_code = 500;

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

class UserErrorException extends SCoreException
{
    public int $http_code = 400;
}

class ServerErrorException extends SCoreException
{
    public int $http_code = 500;
}

/**
 * A fairly common, generic exception.
 */
class PermissionDeniedException extends UserErrorException
{
    public int $http_code = 403;
}

/**
 * This exception is used when an Image cannot be found by ID.
 */
class ImageDoesNotExist extends UserErrorException
{
    public int $http_code = 404;
}

/**
 * This exception is used when a User cannot be found by some criteria.
 */
class UserDoesNotExist extends UserErrorException
{
    public int $http_code = 404;
}

/*
 * For validate_input()
 */
class InvalidInput extends UserErrorException
{
    public int $http_code = 402;
}

/*
 * This is used by the image resizing code when there is not enough memory to perform a resize.
 */
class InsufficientMemoryException extends ServerErrorException
{
}

/*
 * This is used by the image resizing code when there is an error while resizing
 */
class ImageResizeException extends ServerErrorException
{
}
