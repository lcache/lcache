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
     * Note that the Entry objet might be incomplete. Depending on driver
     * implementation the tags property might be empty (null), as it could be
     * non-optimal to load the tags with the entry object.
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

    /**
     * Accessor for the aggregated value of cache-hit events on the driver.
     *
     * @return int
     *   The cache-hits count.
     */
    abstract public function getHits();

    /**
     * Accessor for the aggregated value of cache-miss events on the driver.
     *
     * @return int
     *   The cache-misses count.
     */
    abstract public function getMisses();

    /**
     * Fetch a value from the cache.
     *
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
     *
     * @param Address $address
     * @return boolean
     */
    public function exists(Address $address)
    {
        $value = $this->get($address);
        return !is_null($value);
    }

    /**
     * Clears what's pobbible from the cache storage.
     *
     * @param int $item_limit
     *   Maximum number of items to remove. Defaults clear as much as possible.
     *
     * @return int
     *   Number of items cleared from the cache storage.
     */
    public function collectGarbage($item_limit = null)
    {
        return 0;
    }
}
