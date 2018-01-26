<?php
// XmlHandlerInterface.php

namespace Dormilich\ARIN;

/**
 * Interface to denote a composite object for XML serialisation.
 */
interface XmlHandlerInterface
{
    /**
     * Returns the corresponding XML tag name (without namespace prefix) of the object.
     * 
     * @return string
     */
    public function getName();

    /**
     * Convert the element object into an XML node.
     * 
     * @param SimpleXMLElement $node The parent XML node to append the element to.
     * @return SimpleXMLElement
     */
    public function xmlAppend( \SimpleXMLElement $node );

    /**
     * Convert an XML node into an object.
     * 
     * @param SimpleXMLElement $node The XML node to parse.
     * @return void
     */
    public function xmlParse( \SimpleXMLElement $node );

    /**
     * Return the validity of an Element, Payload or Element group.
     * 
     * @return boolean
     */
    public function isValid();
}
