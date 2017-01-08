<?php

/**
 * @file
 * Factory class for the State manager instances used in L1 cache
 * implementations.
 */

namespace LCache;

/**
 * StateL1Factory class implementation.
 *
 * @author ndobromirov
 */
class StateL1Factory
{
    private $forceDriver = null;

    /**
     * State L1 manager facotry constructor methof.
     *
     * @param string $forceDriver
     *   The factory will create only this type of instances when specified.
     */
    public function __construct($forceDriver = null)
    {
        $this->forceDriver = $forceDriver ? (string) $forceDriver : null;
    }

    /**
     * L1 State manager facotry method.
     *
     * @param string $driverName
     *   Name of the L1 state driver implementation to create. Invalid driver
     *   names will fall-back to the static driver implementation. Currently
     *   available drivers are:
     *   - apcu
     *   - static
     * @param string $pool
     *   Pool ID to use for the data separation.
     *
     * @return \LCache\StateL1Interface
     *   Concrete instance that confirms to the state interface for L1.
     */
    public function create($driverName = null, $pool = null)
    {
        $driver = $this->forceDriver ? $this->forceDriver : $driverName;
        $factoryName = 'create' . mb_convert_case($driver, MB_CASE_LOWER);
        if (!method_exists($this, $factoryName)) {
            $factoryName = 'createStatic';
        }

        $l1CacheInstance = call_user_func([$this, $factoryName], $pool);
        return $l1CacheInstance;
    }

    /**
     * Factory method for the APCu driver.
     *
     * @param string $pool
     * @return \LCache\StateL1APCu
     */
    private function createAPCu($pool)
    {
        return new StateL1APCu($pool);
    }

    /**
     * Factory method for the static driver.
     *
     * @param string $pool
     * @return \LCache\StateL1APCu
     */
    private function createStatic($pool)
    {
        return new StateL1Static();
    }
}
