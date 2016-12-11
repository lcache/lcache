<?php

/**
 * @file
 * Null implementation for L1 state holder.
 */

namespace LCache;

/**
 * Fake/Stub state manager class.
 *
 * @author ndobromirov
 */
class StateL1Null extends StateL1Static implements StateL1Interface
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {inheritdoc}
     */
    public function getHits()
    {
        return 0;
    }

    /**
     * {inheritdoc}
     */
    public function recordHit()
    {
        return false;
    }

    /**
     * {inheritdoc}
     */
    public function getLastAppliedEventID()
    {
        // TODO: Decide on this.
        // Current assumtion is that all events were applied already.
        return PHP_INT_MAX;
    }

    /**
     * {inheritdoc}
     */
    public function setLastAppliedEventID($eventId)
    {
        // Always success.
        return true;
    }
}
