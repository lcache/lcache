<?php

namespace LCache;

/**
 * Base class for the various cache classes.
 */
abstract class LX
{
    abstract public function getEntry(Address $address);
    abstract public function getHits();
    abstract public function getMisses();

    /**
     * Fetch a value from the cache.
     * @param Address $address
     * @return string|null
     */
    public function get(Address $address)
    {
        $entry = $this->getEntry($address);
        if (is_null($entry)) {
            return null;
        }
        return $entry->value;
    }

    /**
     * Determine whether or not the specified Address exists in the cache.
     * @param Address $address
     * @return boolean
     */
    public function exists(Address $address)
    {
        $value = $this->get($address);
        return !is_null($value);
    }
}
