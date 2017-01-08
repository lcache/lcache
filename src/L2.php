<?php

namespace LCache;

abstract class L2 extends LX
{
    /**
     * Incremental update of the L1's state.
     *
     * Based on L1's internal tracker, finds a list of non-appled events. One
     * by one applies them to the given L1 instance, so it can reach the final /
     * correct state as the L2.
     *
     * @var L1 $l1
     *   Instance to apply write events to.
     */
    abstract public function applyEvents(L1 $l1);

    /**
     * Mutator of the L2 state.
     *
     * Writes a $value (assciated with $tags) on $address within $pool. The
     * cache entry will be deemed invalid / expired, when $expires timestamp has
     * passed he current time.
     *
     * @var string $pool
     *   Cache pool to work with.
     * @var \LCache\Address $address
     *   The cache entry address to store into.
     * @var mixed $value
     *   The value to store on $address.
     * @var int $expiration
     *   Unix timestamp to mark the time point when the cache item becomes
     *   invalid. Defaults to NULL - permanent cache item.
     * @var array $tags
     *   List of tag names (string) to associate with the cache item. This will
     *   allow clients to delete multiple cache items by tag.
     * @var bool $value_is_serialized
     *   DO NOT USE IN CLIENT CODE. Defaults to FALSE. This affects internals
     *   for handling the $value parameter and it's only used for testing.
     */
    abstract public function set($pool, Address $address, $value = null, $expiration = null, array $tags = [], $value_is_serialized = false);

    /**
     * Prepares a list of Address instances associated with the provided tag.
     *
     * @var string $tag
     *   Tag name to do the look-up with.
     *
     * @return array
     *   List of the address instances associated with the $tag.
     */
    abstract public function getAddressesForTag($tag);

    /**
     * Utility to find the amount of expired cache items in the storage.
     *
     * @return int
     *   Number of expired items.
     */
    abstract public function countGarbage();

    /**
     * Delete cache items marked by $tag.
     *
     * @var string $tag
     *   Single tag name to use for looking up cache entries for deletition.
     */
    abstract public function deleteTag(L1 $l1, $tag);

    /**
     * Delete cache item from $pool on $address.
     *
     * Depending on Address's value it might be pool, bin or an item data to be
     * deleted from the cache storage.
     *
     * @param string $pool
     *   Pool that the cache item was set in.
     * @param \LCache\Address $address
     *   Address instance that the cache item resides on.
     *
     * @return mixed
     *   Event id of the operation.
     */
    public function delete($pool, Address $address)
    {
        $event_id = $this->set($pool, $address);
        return $event_id;
    }
}
