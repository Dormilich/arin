<?php
// XmlSerializable.php

namespace Dormilich\ARIN;

/**
 * Interface to denote the starting point of an XML serialisation to submit in 
 * RegRWS. Unfortunately neither DOMDocument (needs a document to create elements) 
 * nor SimpleXML (can't add SimpleXML objects to an XML tree) support incremental 
 * serialisation.
 * 
 * Applicable payloads are: 
 *      Customer, Delegation, Message, Net, Org, Phone, Poc, Roa, and Ticket
 */
interface XmlSerializable
{
    /**
     * Create an XML string for this object.
     * 
     * @return string
     */
    public function xmlSerialize();
}
