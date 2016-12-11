<?php

/**
 * @file
 * L1 state manager interface.
 */

namespace LCache;

/**
 * Interface for the state manager drivers used in L1 driver implementations.
 *
 * @author ndobromirov
 */
interface StateL1Interface
{
    /**
     * Records a cache-hit event in the driver.
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
     * This is needed, so on consecuitive requests, we should apply all events
     * newer than this one.
     *
     * @param int $eventId
     *   Event ID to store for future reference.
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
     * @todo: Should the last applied event be cleared as well?
     */
    public function clear();
}
