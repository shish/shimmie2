<?php

/**
 * Class SCoreException
 *
 * A base exception to be caught by the upper levels.
 */
class SCoreException extends Exception {}

/**
 * Class PermissionDeniedException
 *
 * A fairly common, generic exception.
 */
class PermissionDeniedException extends SCoreException {}

/**
 * Class ImageDoesNotExist
 *
 * This exception is used when an Image cannot be found by ID.
 *
 * Example: Image::by_id(-1) returns null
 */
class ImageDoesNotExist extends SCoreException {}

/*
 * For validate_input()
 */
class InvalidInput extends SCoreException {}
