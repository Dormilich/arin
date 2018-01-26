<?php
// Transformable.php

namespace Dormilich\ARIN\Elements;

use Dormilich\ARIN\Transformers\DataTransformerInterface;

interface Transformable
{
    /**
     * Set a new data transformer. 
     * 
     * @param DataTransformerInterface $transformer 
     * @return self
     */
    public function apply( DataTransformerInterface $transformer );
}
