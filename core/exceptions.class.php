<?php
/**
 * A base exception to be caught by the upper levels
 */
class SCoreException extends Exception {}

/**
 * A fairly common, generic exception
 */
class PermissionDeniedException extends SCoreException {}
?>
