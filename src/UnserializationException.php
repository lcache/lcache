<?php

namespace LCache;

class UnserializationException extends \Exception
{
    protected $address;
    protected $serialized_data;

    public function __construct(Address $address, $serialized_data)
    {
        $this->address = $address;
        $this->serialized_data = $serialized_data;
        parent::__construct('Failed to unserialize on cache get');
    }

    public function __toString()
    {
        return __CLASS__ . ': Cache bin "' . $this->address->getBin() . '" and key "' . $this->address->getKey() . '"' . PHP_EOL;
    }

    public function getSerializedData()
    {
        return $this->serialized_data;
    }
}
