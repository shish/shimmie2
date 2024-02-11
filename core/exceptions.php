<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A base exception to be caught by the upper levels.
 */
class SCoreException extends \RuntimeException
{
    public string $error;
    public int $http_code = 500;

    public function __construct(string $msg)
    {
        parent::__construct($msg);
        $this->error = $msg;
    }
}

class InstallerException extends \RuntimeException
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

class UserError extends SCoreException
{
    public int $http_code = 400;
}

class ServerError extends SCoreException
{
    public int $http_code = 500;
}

/**
 * A fairly common, generic exception.
 */
class PermissionDenied extends UserError
{
    public int $http_code = 403;
}

class ObjectNotFound extends UserError
{
    public int $http_code = 404;
}

class ImageNotFound extends ObjectNotFound
{
}

class UserNotFound extends ObjectNotFound
{
}

/*
 * For validate_input()
 */
class InvalidInput extends UserError
{
    public int $http_code = 402;
}
