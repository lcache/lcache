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
class StateL1Null implements StateL1Interface
{
    /**
     * {inheritdoc}
     */
    public function clear()
    {
        // Nothing to do here.
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
    public function getMisses()
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
    public function recordMiss()
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
