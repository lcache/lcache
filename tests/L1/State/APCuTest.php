<?php

/**
 * @file
 * Test class for validating APCu state manager works correct.
 */

namespace LCache\L1\State;

/**
 * APCu L1 state manager test class.
 *
 * @author ndobromirov
 */
class APCuTest extends \LCache\StateL1Test
{
    protected function driverName()
    {
        return 'apcu';
    }
}
