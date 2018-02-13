<?php
// NotFoundException.php

namespace Dormilich\ARIN\Exceptions;

/**
 * Used for indicating a non-existing key in a collection.
 */
class NotFoundException extends \OutOfBoundsException implements ARINException {}
