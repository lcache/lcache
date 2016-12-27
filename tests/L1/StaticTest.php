<?php

/**
 * @file
 * Test file for the Static L1 driver in LCache library.
 */

namespace LCache\L1;

/**
 * StaticTest concrete implementation.
 *
 * @author ndobromirov
 */
class StaticTest extends \LCache\L1CacheTest
{
    /**
     * {@inheritDoc}
     */
    protected function driverName()
    {
        return 'statc';
    }
}
