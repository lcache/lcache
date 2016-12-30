<?php

namespace LCache;

/**
 * Base class for the various cache classes.
 */
abstract class LX
{
    /**
     * Get a cache entry based on address instance.
     *
     * @param \LCache\Address $address
     *   Address to lookup the entry.
     * @return \LCache\Entry|null
     *   The entry found or null (on cache miss).
     *
     * @throws UnserializationException
     *   When the data stored in cache is in invalid format.
     */
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

    public function collectGarbage($item_limit = null)
    {
        return 0;
    }
}
