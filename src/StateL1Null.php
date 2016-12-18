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
        // No cache hits on the null stats.
        return 0;
    }

    /**
     * {inheritdoc}
     */
    public function getLastAppliedEventID()
    {
        // Because we store nothing locally.
        // Behave as if all events are applied.
        return PHP_INT_MAX;
    }
}
