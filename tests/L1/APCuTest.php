<?php

/**
 * @file
 * Test file for the APCu L1 driver in LCache library.
 */

namespace LCache\L1;

/**
 * APCuTest concrete implementation.
 *
 * @author ndobromirov
 */
class APCuTest extends \LCache\L1CacheTest
{
    /**
     * {@inheritDoc}
     */
    protected function driverName()
    {
        return 'apcu';
    }
}
