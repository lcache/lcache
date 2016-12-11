<?php

/**
 * @file
 * L1 state manager implementation.
 */

namespace LCache;

/**
 * L1 statistics manager storing in APCu.
 *
 * @author ndobromirov
 */
class StateL1APCu implements StateL1Interface
{
    /** @var string */
    private $pool;

    /** @var string */
    private $statusKeyHits;

    /** @var string */
    private $statusKeyMisses;

    /** @var string */
    private $statusKeyLastAppliedEventId;

    /**
     * Constructor implementation.
     *
     * @param string $pool
     *   Pool string that this state manager will collect statistics for.
     */
    public function __construct($pool)
    {
        $this->pool = $pool;

        // Using designated variables to speed up key generation during runtime.
        $this->statusKeyHits = 'lcache_status:' . $this->pool . ':hits';
        $this->statusKeyMisses = 'lcache_status:' . $this->pool . ':misses';
        $this->statusKeyLastAppliedEventId = 'lcache_status:' . $this->pool . ':last_applied_event_id';
    }

    /**
     * {inheritdoc}
     */
    public function recordHit()
    {
        return $this->_recordEvent($this->statusKeyHits);
    }

    /**
     * {inheritdoc}
     */
    public function recordMiss()
    {
        return $this->_recordEvent($this->statusKeyMisses);
    }

    /**
     * Utility method to reduce code duplication.
     *
     * @param string $key
     *   Key to store the evet counters in.
     *
     * @return bool
     *   True on success, false otherwise.
     */
    private function _recordEvent($key)
    {
        $success = null;
        apcu_inc($key, 1, $success);
        if ($success !== null && !$success) {
            // @TODO: Remove this fallback when we drop APCu 4.x support.
            // @codeCoverageIgnoreStart
            // Ignore coverage because (1) it's tested with other code and
            // (2) APCu 5.x does not use it.
            $success = apcu_store($key, 1);
            // @codeCoverageIgnoreEnd
        }
        return $success;
    }

    /**
     * {inheritdoc}
     */
    public function getHits()
    {
        $value = apcu_fetch($this->statusKeyHits);
        return $value ? $value : 0;
    }

    /**
     * {inheritdoc}
     */
    public function getMisses()
    {
        $value = apcu_fetch($this->statusKeyMisses);
        return $value ? $value : 0;
    }

    /**
     * {inheritdoc}
     */
    public function getLastAppliedEventID()
    {
        $value = apcu_fetch($this->statusKeyLastAppliedEventId);
        if ($value === false) {
            $value = null;
        }
        return $value;
    }

    /**
     * {inheritdoc}
     */
    public function setLastAppliedEventID($eventId)
    {
        return apcu_store($this->statusKeyLastAppliedEventId, $eventId);
    }

    /**
     * {inheritdoc}
     */
    public function clear()
    {
        apcu_store($this->statusKeyHits, 0);
        apcu_store($this->statusKeyMisses, 0);
        // TODO: Decide on how to handle the last applied event state on clear?
    }
}
