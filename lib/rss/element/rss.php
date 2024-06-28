<?php

namespace Podlove\RSS\Element;

class RSS implements \Sabre\Xml\XmlSerializable
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function xmlSerialize(\Sabre\Xml\Writer $writer): void
    {
        $writer->writeAttribute('version', '2.0');
        $writer->write($this->value);
    }
}
