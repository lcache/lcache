<?php

/**
 * @file
 * Contains the factory class implementation for the L1 cache drivers.
 */

namespace LCache;

/**
 * Class encapsulating the creation logic for all L1 cache driver instances.
 *
 * @author ndobromirov
 */
class L1CacheFactory
{
    /**
     * L1 cache drivers const
     *
     * @todo Change the return value to L1CacheInterface
     *
     * @param string $driverName
     *   Name of the L1 driver implementation to create. One of the DRIVER_*
     *   class constants.
     * @param string $customPool
     *   Pool ID to use for the data separation.
     *
     * @return L1
     *   Concrete instance that confirms to an L1 interface.
     */
    public function create($driverName = null, $customPool = null)
    {
        // Normalize input.
        $pool = $this->getPool($customPool);
        $driver = mb_convert_case($driverName, MB_CASE_LOWER);

        $factoryName = 'create' . $driver;
        if (!method_exists($this, $factoryName)) {
            // TODO: Decide on better fallback (if needed).
            $factoryName = 'createStatic';
        }

        $l1CacheInstance = call_user_func([$this, $factoryName], $pool);
        return $l1CacheInstance;
    }

    /**
     * Factory method for the L1 APCu driver.
     *
     * @param string $pool
     * @return \LCache\APCuL1
     */
    protected function createApcu($pool)
    {
        return new APCuL1($pool, new StateL1APCu($pool));
    }

    /**
     * Factory method for the L1 NULL driver.
     *
     * @param string $pool
     * @return \LCache\NullL1
     */
    protected function createNull($pool)
    {
        return new NullL1($pool, new StateL1Null());
    }

    /**
     * Factory method for the L1 static driver.
     *
     * @param string $pool
     * @return \LCache\StaticL1
     */
    protected function createStatic($pool)
    {
        return new StaticL1($pool, new StateL1Static());
    }

    /**
     * Factory method for the L1 static driver.
     *
     * @param string $pool
     * @return \LCache\SQLiteL1
     */
    protected function createSqlite($pool)
    {
        $hasApcu = function_exists('apcu_fetch');
        // TODO: Maybe implement StateL1SQLite class instead of NULL one.
        $state = $hasApcu ? new StateL1APCu("sqlite-$pool") : new StateL1Null();
        $cache = new SQLiteL1($pool, $state);
        return $cache;
    }

    /**
     * Pool generator utility.
     *
     * @param string $pool
     *   Custom pool to use. Defaults to NULL. If the  default is uesed, it will
     *   atempt to generate a pool value for use.
     *
     * @return string
     *   Pool value based on input and/or environment variables / state.
     */
    protected function getPool($pool = null)
    {
        if (!is_null($pool)) {
            $result = (string) $pool;
        } elseif (isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['SERVER_PORT'])) {
            $result = $_SERVER['SERVER_ADDR'] . '-' . $_SERVER['SERVER_PORT'];
        } else {
            $result = $this->generateUniqueID();
        }
        return $result;
    }

    /**
     * Pool generation utility.
     *
     * @see L1CacheFactory::getPool()
     *
     * @return string
     */
    protected function generateUniqueID()
    {
        // @TODO: Replace with a persistent but machine-local (and unique) method.
        return uniqid('', true) . '-' . mt_rand();
    }
}
