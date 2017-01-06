<?php

/**
 * @file
 * L1 state manager interface.
 */

namespace LCache;

/**
 * Interface for the state manager drivers used in L1 driver implementations.
 *
 * This interface is separated from L1 drivers, due to the need to separate L1
 * storage implementations from L1 statistics storage. It was proven that L1
 * SQLite driver implementation is much slower, unless APCu is used for the
 * events data tracking. This pushed the need to separate statistics storage
 * from the main storage of L1 data and to allow free combinations between them.
 *
 * @author ndobromirov
 */
interface StateL1Interface
{
    /**
     * Records a cache-hit event in the driver.
     *
     * @return bool
     *   TRUE on success.
     */
    public function recordHit();

    /**
     * Accessor for the aggregated value of cache-hit events on the driver.
     *
     * @return int
     *   The cache-hits count.
     */
    public function getHits();

    /**
     * Records a cache-miss event in the driver.
     *
     * @return bool
     *   TRUE on success.
     */
    public function recordMiss();

    /**
     * Accessor for the aggregated value of cache-miss events on the driver.
     *
     * @return int
     *   The cache-misses count.
     */
    public function getMisses();

    /**
     * Stores the last applied cache mutation event id in the L1 cache.
     *
     * This is needed, so on consecuitive requests we can incrementally update
     * the storage data. Clients should apply all events newer than this one.
     * When an older event is passed, it will be ignored.
     *
     * @param int $eventId
     *   Event ID to store for future reference.
     *
     * @return bool
     *   TRUE on successful assignment, FALSE when any of the following happens:
     *   - Failed to write the state to the storage.
     *   - Client atempted to write older event than the already stored one.
     */
    public function setLastAppliedEventID($eventId);

    /**
     * Accessor for the last applied event id on the L1 driver.
     *
     * @see StateL1Interface::setLastAppliedEventID($eventId)
     *
     * @return int
     *   The EventID stored or NULL.
     */
    public function getLastAppliedEventID();

    /**
     * Clears the collected statistical data.
     *
     * @return bool
     *   TRUE on success, FALSE otherwise.
     */
    public function clear();
}
