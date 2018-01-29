<?php
// ParserException.php

namespace Dormilich\ARIN\Exceptions;

/**
 * Thrown if an XML element does not conform to the defined XML structure in the 
 * parsing object.
 */
class ParserException extends \RuntimeException implements ARINException {}
