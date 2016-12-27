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

    protected function setUp()
    {
        $run_test = false;
        if (function_exists('apcu_store')) {
            $value_to_store = 'test-value-' . rand(0, PHP_INT_MAX);
            apcu_store('test_key', $value_to_store);
            $run_test = apcu_fetch('test_key')  === $value_to_store;
        }

        if (!$run_test) {
            $this->markTestSkipped('The APCu extension is not installed, enabled (for the CLI), or functional.');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function driverName()
    {
        return 'apcu';
    }
}
