<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of StateL1APCu
 *
 * @author ndobromirov
 */
class StateL1APCu implements StateL1Interface
{
    private $pool;

    /** @var string */
    private $statusKeyHits;

    /** @var string */
    private $statusKeyMisses;

    /** @var string */
    private $statusKeyLastAppliedEventId;

    public function __construct($pool)
    {
        $this->pool = $pool;

        // Using designated variables to speed up key generation during runtime.
        $this->statusKeyHits = 'lcache_status:' . $this->pool . ':hits';
        $this->statusKeyMisses = 'lcache_status:' . $this->pool . ':misses';
        $this->statusKeyLastAppliedEventId = 'lcache_status:' . $this->pool . ':last_applied_event_id';
    }

    public function recordHit()
    {
        apcu_inc($this->statusKeyHits, 1, $success);
        if (!$success) {
            // @TODO: Remove this fallback when we drop APCu 4.x support.
            // @codeCoverageIgnoreStart
            // Ignore coverage because (1) it's tested with other code and
            // (2) APCu 5.x does not use it.
            apcu_store($this->statusKeyHits, 1);
            // @codeCoverageIgnoreEnd
        }
    }

    public function recordMiss()
    {
        apcu_inc($this->statusKeyMisses, 1, $success);
        if (!$success) {
            // @TODO: Remove this fallback when we drop APCu 4.x support.
            // @codeCoverageIgnoreStart
            // Ignore coverage because (1) it's tested with other code and
            // (2) APCu 5.x does not use it.
            apcu_store($this->statusKeyMisses, 1);
            // @codeCoverageIgnoreEnd
        }
    }

    public function getHits()
    {
        $value = apcu_fetch($this->statusKeyHits);
        return $value ? $value : 0;
    }

    public function getMisses()
    {
        $value = apcu_fetch($this->statusKeyMisses);
        return $value ? $value : 0;
    }

    public function getLastAppliedEventID()
    {
        $value = apcu_fetch($this->statusKeyLastAppliedEventId);
        if ($value === false) {
            $value = null;
        }
        return $value;
    }

    public function setLastAppliedEventID($eventId)
    {
        return apcu_store($this->statusKeyLastAppliedEventId, $eventId);
    }

    public function clear()
    {
        apcu_store($this->statusKeyHits, 0);
        apcu_store($this->statusKeyMisses, 0);
        // TODO: Decide on how to handle the last applied event state on clear?
    }
}
