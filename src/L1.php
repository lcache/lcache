<?php

namespace LCache;

abstract class L1 extends LX
{
    protected $pool;

    /** @var StateL1Interface */
    protected $state;

    public function __construct($pool = null, StateL1Interface $state = null)
    {
        if (!is_null($pool)) {
            $this->pool = $pool;
        } elseif (isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['SERVER_PORT'])) {
            $this->pool = $_SERVER['SERVER_ADDR'] . '-' . $_SERVER['SERVER_PORT'];
        } else {
            $this->pool = $this->generateUniqueID();
        }

        if (!is_null($state)) {
            $this->state = $state;
        } elseif (function_exists('apcu_fetch')) {
            $this->state = new StateL1APCu($this->pool);
        } else {
            $this->state = new StateL1Static();
        }
    }

    protected function generateUniqueID()
    {
        // @TODO: Replace with a persistent but machine-local (and unique) method.
        return uniqid('', true) . '-' . mt_rand();
    }

    public function getLastAppliedEventID()
    {
        return $this->state->getLastAppliedEventID();
    }

    public function setLastAppliedEventID($event_id)
    {
        return $this->state->setLastAppliedEventID($event_id);
    }

    public function getPool()
    {
        return $this->pool;
    }

    public function set($event_id, Address $address, $value = null, $expiration = null)
    {
        return $this->setWithExpiration($event_id, $address, $value, $_SERVER['REQUEST_TIME'], $expiration);
    }

    abstract public function isNegativeCache(Address $address);
    abstract public function getKeyOverhead(Address $address);
    abstract public function setWithExpiration($event_id, Address $address, $value, $created, $expiration = null);
    abstract public function delete($event_id, Address $address);

    public function getHits()
    {
        return $this->state->getHits();
    }

    public function getMisses()
    {
        return $this->state->getMisses();
    }

    protected function recordHit()
    {
        $this->state->recordHit();
    }

    protected function recordMiss()
    {
        $this->state->recordMiss();
    }
}
