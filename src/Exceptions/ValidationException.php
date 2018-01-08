<?php
// ValidationException.php

namespace Dormilich\ARIN\Exceptions;

/**
 * Thrown if a value does not pass validation.
 */
class ValidationException extends \UnexpectedValueException implements ARINException {}
