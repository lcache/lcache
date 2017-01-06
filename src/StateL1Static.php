<?php

/**
 * @file
 * In-memory implementation of statistics storage for L1 drivers.
 */

namespace LCache;

/**
 * Description of StateL1Static
 *
 * @author ndobromirov
 */
class StateL1Static implements StateL1Interface
{
    /** @var int Container variable for the cache-hit count. */
    protected $hits;

    /** @var int Container variable for the cache-miss count. */
    protected $misses;

    /** @var int Container variable for the last applied event id value. */
    protected $last_applied_event_id;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->last_applied_event_id = null;
        $this->clear();
    }

    /**
     * {inheritdoc}
     */
    public function recordHit()
    {
        $this->hits++;
        return true;
    }

    /**
     * {inheritdoc}
     */
    public function recordMiss()
    {
        $this->misses++;
        return true;
    }

    /**
     * {inheritdoc}
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * {inheritdoc}
     */
    public function getMisses()
    {
        return $this->misses;
    }

    /**
     * {inheritdoc}
     */
    public function getLastAppliedEventID()
    {
        return $this->last_applied_event_id;
    }

    /**
     * {inheritdoc}
     */
    public function setLastAppliedEventID($eventId)
    {
        return $this->getLastAppliedEventID() <= $eventId
            && ($this->last_applied_event_id = $eventId);
    }

    /**
     * {inheritdoc}
     */
    public function clear()
    {
        $this->hits = $this->misses = 0;
        return true;
    }
}
